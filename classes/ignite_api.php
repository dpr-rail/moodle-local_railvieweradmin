<?php
namespace local_railvieweradmin;

/**
 * Kleiner Client für die ignite.video REST API (https://docs.ignite.video/).
 *
 * Nutzt Bearer-Token-Authentifizierung gegen https://app.ignitevideo.cloud/api/videos.
 * Die API scheint auf Payload CMS zu basieren (Antwortformat mit docs/totalDocs/...),
 * daher wird für die Titelsuche dessen "where[field][contains]"-Query-Syntax verwendet.
 */
class ignite_api {

    /** @var string */
    private $apikey;

    /** @var string */
    private $baseurl = 'https://app.ignitevideo.cloud/api';

    public function __construct() {
        $this->apikey = get_config('local_railvieweradmin', 'ignite_api_key');
    }

    /**
     * Holt eine Seite der Videos des Accounts.
     */
    public function get_user_videos($page = 1, $per_page = 25) {
        return $this->request([
            'page' => $page,
            'limit' => $per_page,
            'sort' => '-createdAt',
        ]);
    }

    /**
     * Sucht Videos, deren Titel ODER Beschreibung den Suchbegriff enthält (Payload CMS'
     * "where[or][N][feld][contains]"-Syntax, live gegen die echte API verifiziert - eine reine
     * Titelsuche fand z.B. einen nur in der Beschreibung vorkommenden Begriff gar nicht).
     */
    public function search_videos($query, $page = 1, $per_page = 25) {
        return $this->request([
            'page' => $page,
            'limit' => $per_page,
            'sort' => '-createdAt',
            'where' => [
                'or' => [
                    ['title' => ['contains' => $query]],
                    ['description' => ['contains' => $query]],
                ],
            ],
        ]);
    }

    /**
     * Legt ein neues Video-Objekt bei ignite.video an und liefert nur die vorsignierte S3-Upload-
     * URL zurück (siehe docs.ignite.video/api-reference/videos/upload) - die Datei-Bytes werden
     * NICHT mehr von PHP hochgeladen, sondern direkt vom Browser per PUT an diese URL geschickt
     * (siehe uploadVideo() in section_form.php). Grund: siehe ausführlicher Kommentar bei
     * vimeo_api::create_upload() - ein Umweg über unseren PHP-Server ist für große Dateien
     * (mehrere GB) durch dessen upload_max_filesize/post_max_size, Ausführungszeit und Speicher
     * praktisch nicht nutzbar.
     *
     * @param string $title
     * @param string $description
     * @param string $visibility 'public' oder 'private'
     * @return array ['success' => bool, 'id' => string|null, 'upload_url' => string|null, 'message' => string]
     */
    public function create_upload($title, $description, $visibility) {
        if (empty($this->apikey)) {
            return ['success' => false, 'message' => 'No API key configured'];
        }

        // "description" wird von der Create-Antwort selbst nicht zurückgegeben, live gegen die
        // echte API bestätigt aber tatsächlich gespeichert (per Abruf des angelegten Videos
        // direkt danach geprüft).
        $createbody = json_encode([
            'title' => $title,
            'description' => $description,
            'visibility' => $visibility,
        ]);
        $ch = curl_init($this->baseurl . '/videos/upload');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $createbody,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
        ]);
        $createresponse = curl_exec($ch);
        $createcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($createcode < 200 || $createcode >= 300) {
            return ['success' => false, 'message' => "Video creation failed (HTTP $createcode): " . $createresponse];
        }

        $createdata = json_decode($createresponse, true);
        $videoid = $createdata['videoId'] ?? null;
        $signedurl = $createdata['signedUrl'] ?? null;
        if (empty($videoid) || empty($signedurl)) {
            return ['success' => false, 'message' => 'ignite.video response missing videoId/signedUrl'];
        }

        return ['success' => true, 'id' => (string) $videoid, 'upload_url' => $signedurl, 'name' => $title];
    }

    /**
     * Holt ein einzelnes Video-Objekt (siehe docs.ignite.video/api-reference/videos/object) -
     * insbesondere "status" (COMPLETE/PROGRESSING/REPLACING/NO_FILE/ERROR) und "statusProgress"
     * (Kodierfortschritt 0-100, nur während PROGRESSING/REPLACING relevant). Von ignite.video
     * selbst als Polling-Ziel für einen Fortschrittsbalken in der eigenen UI empfohlen.
     *
     * @param string $id
     * @return array|false Rohes API-Antwort-Array, oder false bei Fehler/fehlendem API-Key
     */
    public function get_video($id) {
        if (empty($this->apikey)) {
            return false;
        }

        $ch = curl_init($this->baseurl . '/videos/' . urlencode($id));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apikey,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200) {
            return json_decode($response, true);
        }

        return false;
    }

    private function request(array $params) {
        if (empty($this->apikey)) {
            return false;
        }

        // Dritter Parameter '&' erzwungen: Moodle setzt in lib/setup.php bewusst
        // ini_set('arg_separator.output', '&amp;') (historische XHTML-Absicherung), worauf sich
        // http_build_query() standardmäßig stützt. Für eine echte HTTP-Anfrage (nicht für in
        // HTML eingebettetes Markup) ist "&amp;" als Trenner ungültig - die API sah dadurch nur
        // den ersten Parameter, alle weiteren (inkl. "where"-Filter) wurden zu dessen Wert.
        $url = $this->baseurl . '/videos?' . http_build_query($params, '', '&');
        $headers = [
            'Authorization: Bearer ' . $this->apikey,
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200) {
            return json_decode($response, true);
        }

        return false;
    }
}
