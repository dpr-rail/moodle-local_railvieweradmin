<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Minimal S3 client (AWS Signature V4, presigned URLs) for S3-compatible
 * storage such as Hetzner Object Storage. No SDK dependency.
 *
 * @package    local_railvieweradmin
 */
class s3_client {
    /** @var string Endpoint scheme + host (+port), without trailing slash. */
    private $endpoint;
    private $host;
    private $region;
    private $bucket;
    private $accesskey;
    private $secretkey;
    private $pathstyle;

    /** File extensions offered in the video picker. */
    const VIDEO_EXTENSIONS = ['mp4', 'm4v', 'webm', 'ogv', 'mov'];

    /**
     * @param bool $forupload true = Zugangsdaten für Browser-Uploads verwenden. Sind dafür keine
     *        eigenen Zugangsdaten (s3_upload_access_key/-secret_key) hinterlegt, gelten die
     *        allgemeinen S3-Zugangsdaten.
     */
    public function __construct(bool $forupload = false) {
        $this->endpoint = rtrim(trim((string) get_config('local_railvieweradmin', 's3_endpoint')), '/');
        if ($this->endpoint !== '' && !preg_match('#^https?://#i', $this->endpoint)) {
            $this->endpoint = 'https://' . $this->endpoint;
        }
        $this->host = (string) parse_url($this->endpoint, PHP_URL_HOST);
        $port = parse_url($this->endpoint, PHP_URL_PORT);
        if ($port) {
            $this->host .= ':' . $port;
        }
        $this->region = trim((string) get_config('local_railvieweradmin', 's3_region')) ?: 'us-east-1';
        $this->bucket = trim((string) get_config('local_railvieweradmin', 's3_bucket'));
        $this->accesskey = trim((string) get_config('local_railvieweradmin', 's3_access_key'));
        $this->secretkey = trim((string) get_config('local_railvieweradmin', 's3_secret_key'));
        $this->pathstyle = (bool) get_config('local_railvieweradmin', 's3_pathstyle');
        if ($forupload) {
            $uploadkey = trim((string) get_config('local_railvieweradmin', 's3_upload_access_key'));
            $uploadsecret = trim((string) get_config('local_railvieweradmin', 's3_upload_secret_key'));
            if ($uploadkey !== '' && $uploadsecret !== '') {
                $this->accesskey = $uploadkey;
                $this->secretkey = $uploadsecret;
            }
        }
    }

    public function is_configured(): bool {
        return $this->host !== '' && $this->bucket !== '' && $this->accesskey !== '' && $this->secretkey !== '';
    }

    /**
     * Presigned GET URL for a stored object (used for video playback).
     */
    public function presign_get(string $key, ?int $expires = null): string {
        return $this->presign('GET', $key, [], $expires ?? self::default_expiry());
    }

    /**
     * Presigned PUT URL for direct browser upload.
     */
    public function presign_put(string $key, int $expires = 3600): string {
        return $this->presign('PUT', $key, [], $expires);
    }

    /**
     * Lists video objects below a prefix.
     *
     * @return array ['objects' => [[key,size,modified]], 'next' => token|null]
     * @throws \moodle_exception on request failure
     */
    public function list_videos(string $prefix = '', string $token = '', int $max = 100): array {
        $query = ['list-type' => '2', 'max-keys' => (string) $max];
        if ($prefix !== '') {
            $query['prefix'] = $prefix;
        }
        if ($token !== '') {
            $query['continuation-token'] = $token;
        }
        $xml = $this->request($this->presign('GET', '', $query, 120));

        $objects = [];
        foreach ($xml->Contents as $item) {
            $key = (string) $item->Key;
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if (substr($key, -1) === '/' || !in_array($ext, self::VIDEO_EXTENSIONS, true)) {
                continue;
            }
            $objects[] = [
                'key' => $key,
                'size' => (int) $item->Size,
                'modified' => (string) $item->LastModified,
            ];
        }
        $next = ((string) $xml->IsTruncated === 'true') ? (string) $xml->NextContinuationToken : null;
        return ['objects' => $objects, 'next' => $next];
    }

    /**
     * Verifies credentials, endpoint and bucket.
     *
     * @return true|string true on success, otherwise an error message
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return get_string('s3_not_configured', 'local_railvieweradmin');
        }
        try {
            $this->request($this->presign('GET', '', ['list-type' => '2', 'max-keys' => '1'], 120));
        } catch (\moodle_exception $e) {
            return $e->getMessage();
        }
        return true;
    }

    /**
     * Checks that an object key is a sane video key (no traversal, allowed extension).
     */
    public static function is_valid_key(string $key): bool {
        if ($key === '' || strlen($key) > 1024 || preg_match('#(^|/)\.\.(/|$)|[\x00-\x1f]#', $key)) {
            return false;
        }
        return in_array(strtolower(pathinfo($key, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS, true);
    }

    public static function default_expiry(): int {
        $hours = (int) get_config('local_railvieweradmin', 's3_url_expiry_hours');
        $hours = $hours > 0 ? min($hours, 168) : 12;
        return $hours * 3600;
    }

    private function request(string $url): \SimpleXMLElement {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $body = $curl->get($url, null, ['CURLOPT_TIMEOUT' => 20]);
        $code = (int) ($curl->get_info()['http_code'] ?? 0);
        if ($code !== 200) {
            $detail = '';
            if (is_string($body) && ($err = @simplexml_load_string($body))) {
                $detail = trim((string) $err->Code . ' ' . (string) $err->Message);
            } else if ($curl->get_errno()) {
                $detail = $curl->error;
            }
            throw new \moodle_exception('s3_request_failed', 'local_railvieweradmin', '', trim("HTTP $code $detail"));
        }
        $xml = @simplexml_load_string($body);
        if ($xml === false) {
            throw new \moodle_exception('s3_request_failed', 'local_railvieweradmin', '', 'invalid XML response');
        }
        return $xml;
    }

    /**
     * Builds an AWS SigV4 query-string presigned URL.
     */
    private function presign(string $method, string $key, array $query, int $expires): string {
        $expires = max(1, min($expires, 604800));
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $scope = "$date/{$this->region}/s3/aws4_request";

        if ($this->pathstyle) {
            $host = $this->host;
            $path = '/' . $this->bucket . ($key !== '' ? '/' . $this->encode_path($key) : '');
            if ($key === '') {
                $path .= '/';
            }
            $base = $this->scheme() . '://' . $host;
        } else {
            $host = $this->bucket . '.' . $this->host;
            $path = '/' . $this->encode_path($key);
            $base = $this->scheme() . '://' . $host;
        }

        $query += [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->accesskey . '/' . $scope,
            'X-Amz-Date' => $now,
            'X-Amz-Expires' => (string) $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($query);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
        }
        $canonicalquery = implode('&', $pairs);

        $canonical = implode("\n", [$method, $path, $canonicalquery, "host:$host\n", 'host', 'UNSIGNED-PAYLOAD']);
        $tosign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonical)]);

        $k = hash_hmac('sha256', $date, 'AWS4' . $this->secretkey, true);
        $k = hash_hmac('sha256', $this->region, $k, true);
        $k = hash_hmac('sha256', 's3', $k, true);
        $k = hash_hmac('sha256', 'aws4_request', $k, true);
        $signature = hash_hmac('sha256', $tosign, $k);

        return $base . $path . '?' . $canonicalquery . '&X-Amz-Signature=' . $signature;
    }

    private function scheme(): string {
        return strtolower((string) parse_url($this->endpoint, PHP_URL_SCHEME)) === 'http' ? 'http' : 'https';
    }

    private function encode_path(string $key): string {
        return implode('/', array_map('rawurlencode', explode('/', $key)));
    }
}
