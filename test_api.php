<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Part of the local_railvieweradmin plugin.
 *
 * @package    local_railvieweradmin
 * @copyright  dpr-rail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:managesettings', $context);

header('Content-Type: application/json');

/**
 * Führt einen einzelnen curl-GET aus und gibt [http_code, body, curl_error] zurück.
 * http_code bleibt 0, wenn die Anfrage gar nicht erst durchkam (DNS/Netzwerk/Timeout) -
 * dafür steckt dann etwas Brauchbares in $error statt einem nichtssagenden "HTTP-Code: 0".
 */
function local_railvieweradmin_api_test_request($url, array $headers, $userpwd = null) {
    $ch = curl_init($url);
    $options = [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 15,
    ];
    if ($userpwd !== null) {
        $options[CURLOPT_USERPWD] = $userpwd;
    }
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return [$httpcode, $response, $error];
}

function local_railvieweradmin_api_test_result($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

/**
 * "404" oder, falls curl gar keine Antwort bekam (DNS/Timeout/TLS-Fehler, http_code bleibt 0),
 * "n/a – Could not resolve host" statt eines nichtssagenden nackten Zahlencodes.
 */
function local_railvieweradmin_api_test_detail($httpcode, $error) {
    $detail = $httpcode ?: 'n/a';
    if ($error !== '') {
        $detail .= ' – ' . $error;
    }
    return $detail;
}

$service = optional_param('service', 'vimeo', PARAM_ALPHA);

if ($service === 'vimeo') {
    $accesstoken = get_config('local_railvieweradmin', 'vimeo_access_token');
    if (empty($accesstoken)) {
        local_railvieweradmin_api_test_result(false, get_string('api_test_no_credentials', 'local_railvieweradmin'));
    }

    [$httpcode, $response, $error] = local_railvieweradmin_api_test_request('https://api.vimeo.com/me', [
        'Authorization: Bearer ' . $accesstoken,
        'Accept: application/vnd.vimeo.*+json;version=3.4',
    ]);

    if ($httpcode === 200) {
        $data = json_decode($response, true);
        $name = $data['name'] ?? '?';
        $account = $data['account'] ?? '?';
        local_railvieweradmin_api_test_result(true, get_string('api_test_success_vimeo', 'local_railvieweradmin', "$name ($account)"));
    }
    local_railvieweradmin_api_test_result(false, get_string('api_test_failed_http', 'local_railvieweradmin',
        local_railvieweradmin_api_test_detail($httpcode, $error)));

} else if ($service === 'ignite') {
    $apikey = get_config('local_railvieweradmin', 'ignite_api_key');
    if (empty($apikey)) {
        local_railvieweradmin_api_test_result(false, get_string('api_test_no_credentials', 'local_railvieweradmin'));
    }

    [$httpcode, $response, $error] = local_railvieweradmin_api_test_request(
        'https://app.ignitevideo.cloud/api/videos?limit=1',
        ['Authorization: Bearer ' . $apikey, 'Accept: application/json']
    );

    if ($httpcode === 200) {
        $data = json_decode($response, true);
        $total = $data['totalDocs'] ?? '?';
        local_railvieweradmin_api_test_result(true, get_string('api_test_success_ignite', 'local_railvieweradmin', $total));
    }
    local_railvieweradmin_api_test_result(false, get_string('api_test_failed_http', 'local_railvieweradmin',
        local_railvieweradmin_api_test_detail($httpcode, $error)));

} else if ($service === 'ebula') {
    $apiurl = get_config('local_railvieweradmin', 'ebula_api_url');
    $apiuser = get_config('local_railvieweradmin', 'ebula_api_user');
    $apipass = get_config('local_railvieweradmin', 'ebula_api_password');
    $apikey = get_config('local_railvieweradmin', 'ebula_api_key');
    if (empty($apiurl) || empty($apiuser) || empty($apikey)) {
        local_railvieweradmin_api_test_result(false, get_string('api_test_no_credentials', 'local_railvieweradmin'));
    }

    // Dummy-Zugnummer nur um die Authentifizierung zu prüfen - ob dafür ein Fahrplan existiert,
    // ist für den Verbindungstest irrelevant, nur ob die Zugangsdaten überhaupt akzeptiert
    // werden (kein 401/403). Siehe ebulas.php für den identischen Auth-Header-Aufbau.
    // '&' als Trenner erzwingen (Moodle setzt arg_separator.output auf '&amp;', siehe
    // ignite_api.php/ebulas.php für dieselbe Korrektur) - hier aktuell nur ein Parameter, also
    // nicht akut betroffen, aber inkonsistent zu den anderen http_build_query()-Aufrufen und
    // bricht sofort, sobald ein zweiter Parameter dazukommt.
    $testurl = $apiurl . '?' . http_build_query(['train-number' => 1], '', '&');
    [$httpcode, $response, $error] = local_railvieweradmin_api_test_request(
        $testurl,
        ['Accept: application/zip', 'apikey: ' . $apikey],
        "$apiuser:$apipass"
    );

    if ($httpcode === 401 || $httpcode === 403) {
        local_railvieweradmin_api_test_result(false, get_string('api_test_failed_http', 'local_railvieweradmin',
            local_railvieweradmin_api_test_detail($httpcode, $error)));
    } else if ($httpcode >= 200 && $httpcode < 500) {
        // 200 (Treffer), 400 (ungültiger Parameter) oder 404 (kein Treffer zur Dummy-Zugnummer)
        // gelten hier alle als Erfolg des Verbindungstests: die Zugangsdaten wurden akzeptiert,
        // nur die konkrete Anfrage selbst führte zu keinem/einem anderen Ergebnis.
        local_railvieweradmin_api_test_result(true, get_string('api_test_success_ebula', 'local_railvieweradmin', $httpcode));
    } else {
        local_railvieweradmin_api_test_result(false, get_string('api_test_failed_http', 'local_railvieweradmin',
            local_railvieweradmin_api_test_detail($httpcode, $error)));
    }

} else if ($service === 'sharepoint') {
    require_once(__DIR__ . '/classes/sharepoint_api.php');
    $api = new \local_railvieweradmin\sharepoint_api();
    $result = $api->test_connection();
    local_railvieweradmin_api_test_result($result['success'], $result['message']);

} else if ($service === 'webhook') {
    $url = trim((string) get_config('local_railvieweradmin', 'webhook_url'));
    if ($url === '') {
        local_railvieweradmin_api_test_result(false, get_string('api_test_no_credentials', 'local_railvieweradmin'));
    }
    $body = json_encode(['event' => 'ping', 'timestamp' => time()]);
    $headers = ['Content-Type: application/json'];
    $secret = (string) get_config('local_railvieweradmin', 'webhook_secret');
    if ($secret !== '') {
        $headers[] = 'X-Railviewer-Signature: sha256=' . hash_hmac('sha256', $body, $secret);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $httpcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($httpcode >= 200 && $httpcode < 300) {
        local_railvieweradmin_api_test_result(true, get_string('api_test_success_webhook', 'local_railvieweradmin', $httpcode));
    }
    local_railvieweradmin_api_test_result(false, get_string('api_test_failed_http', 'local_railvieweradmin',
        local_railvieweradmin_api_test_detail($httpcode, $error)));

} else if ($service === 'compliancemail') {
    $to = \local_railvieweradmin\compliance_report::parse_recipients(get_config('local_railvieweradmin', 'compliance_mail_to'));
    if (!$to) {
        local_railvieweradmin_api_test_result(false, get_string('api_test_no_credentials', 'local_railvieweradmin'));
    }
    $ok = \local_railvieweradmin\compliance_report::send_mail($to, get_string('compliance_mail_test_subject', 'local_railvieweradmin'),
        get_string('compliance_mail_test_body', 'local_railvieweradmin'));
    local_railvieweradmin_api_test_result($ok, $ok
        ? get_string('compliance_mail_test_ok', 'local_railvieweradmin', implode(', ', $to))
        : get_string('compliance_mail_test_failed', 'local_railvieweradmin'));

} else if ($service === 's3') {
    $result = (new \local_railvieweradmin\s3_client())->test_connection();
    local_railvieweradmin_api_test_result($result === true,
        $result === true ? get_string('s3_test_ok', 'local_railvieweradmin') : $result);

} else {
    local_railvieweradmin_api_test_result(false, 'Unknown service: ' . $service);
}
