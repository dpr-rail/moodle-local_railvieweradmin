<?php
namespace local_railvieweradmin;

/**
 * Kleiner Client für SharePoint-Videos über die Microsoft Graph API (App-only-Zugriff per
 * Client-Credentials-OAuth2-Flow, siehe docs/sharepoint-einrichtung.md für die Einrichtung
 * der dafür nötigen Azure-AD/Entra-App-Registrierung).
 *
 * Wichtiger Unterschied zu vimeo_api/ignite_api: dort wird das Video direkt in einen Player
 * eingebettet (iframe). Für "Stream (on SharePoint)"-Videos gibt es dafür keine dokumentierte,
 * per JavaScript steuerbare Player-API (kein postMessage-Protokoll für Wiedergabeposition/
 * Events wie bei Vimeo) - Fortschrittsleiste, Pflicht-Stopps, Anti-Vorspul-Sperre und
 * Geschwindigkeitsregler in mod_railviewer/play.php wären darüber also nicht umsetzbar.
 * Stattdessen wird die rohe Videodatei über Graph aufgelöst und wie eine hochgeladene Datei
 * (nativer <video>-Tag) ausgeliefert - siehe sharepoint_stream.php. Dadurch funktionieren alle
 * bestehenden Player-Funktionen unverändert, ganz ohne SharePoint-spezifischen Code in
 * play.php's JavaScript.
 */
class sharepoint_api {

    /** @var string */
    private $tenantid;

    /** @var string */
    private $clientid;

    /** @var string */
    private $clientsecret;

    /** @var string */
    private $siteurl;

    /** @var string */
    private $libraryname;

    public function __construct() {
        $this->tenantid = trim((string) get_config('local_railvieweradmin', 'sharepoint_tenant_id'));
        $this->clientid = trim((string) get_config('local_railvieweradmin', 'sharepoint_client_id'));
        $this->clientsecret = (string) get_config('local_railvieweradmin', 'sharepoint_client_secret');
        $this->siteurl = trim((string) get_config('local_railvieweradmin', 'sharepoint_site_url'));
        $this->libraryname = trim((string) get_config('local_railvieweradmin', 'sharepoint_library_name'));
    }

    private function configured() {
        return $this->tenantid !== '' && $this->clientid !== '' && $this->clientsecret !== '' && $this->siteurl !== '';
    }

    /**
     * App-only Access Token per OAuth2-Client-Credentials-Flow (Anmeldung als die App selbst,
     * nicht als ein bestimmter Nutzer - siehe Einrichtungsanleitung, Abschnitt "Warum
     * App-only?"). Wird für die Gültigkeitsdauer (meist 1h) in der Application-Cache
     * zwischengespeichert, damit nicht bei jedem Seitenaufruf ein neuer Token geholt werden
     * muss.
     *
     * @return string|false
     */
    private function get_token() {
        if (!$this->configured()) {
            return false;
        }

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'local_railvieweradmin', 'sharepoint_token');
        $cached = $cache->get('token_' . md5($this->tenantid . '|' . $this->clientid));
        if ($cached && !empty($cached['expires']) && $cached['expires'] > time() + 60) {
            return $cached['access_token'];
        }

        $tokenurl = 'https://login.microsoftonline.com/' . rawurlencode($this->tenantid) . '/oauth2/v2.0/token';
        $postfields = http_build_query([
            'client_id' => $this->clientid,
            'client_secret' => $this->clientsecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ], '', '&'); // '&' erzwungen statt Moodles arg_separator.output="&amp;" - siehe ignite_api.php.

        $ch = curl_init($tokenurl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
            return false;
        }

        $data = json_decode($response, true);
        $accesstoken = $data['access_token'] ?? null;
        $expiresin = (int) ($data['expires_in'] ?? 3600);
        if (empty($accesstoken)) {
            return false;
        }

        $cache->set('token_' . md5($this->tenantid . '|' . $this->clientid), [
            'access_token' => $accesstoken,
            'expires' => time() + $expiresin,
        ]);

        return $accesstoken;
    }

    private function graph_get($url) {
        $token = $this->get_token();
        if (!$token) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200) {
            return json_decode($response, true);
        }
        return false;
    }

    /**
     * Löst die konfigurierte SharePoint-Site-URL (z.B. https://contoso.sharepoint.com/sites/Team)
     * in eine Graph-Site-ID auf. Siehe learn.microsoft.com/graph/api/site-getbypath.
     *
     * @return string|false
     */
    private function resolve_site_id() {
        $parts = parse_url($this->siteurl);
        if (empty($parts['host'])) {
            return false;
        }
        $hostname = $parts['host'];
        $path = trim($parts['path'] ?? '', '/');

        $url = 'https://graph.microsoft.com/v1.0/sites/' . rawurlencode($hostname);
        if ($path !== '') {
            $url .= ':/' . implode('/', array_map('rawurlencode', explode('/', $path))) . ':';
        }

        $data = $this->graph_get($url);
        return $data['id'] ?? false;
    }

    /**
     * Löst die konfigurierte Dokumentbibliothek (Drive) innerhalb der Site auf. Leeres
     * sharepoint_library_name => Standard-Dokumentbibliothek der Site (die Bibliothek, in der
     * "Stream (on SharePoint)"-Videos meist ohnehin landen, wenn keine eigene Videobibliothek
     * angelegt wurde).
     *
     * @return string|false
     */
    private function resolve_drive_id() {
        $siteid = $this->resolve_site_id();
        if (!$siteid) {
            return false;
        }

        if ($this->libraryname === '') {
            $data = $this->graph_get('https://graph.microsoft.com/v1.0/sites/' . rawurlencode($siteid) . '/drive');
            return $data['id'] ?? false;
        }

        $data = $this->graph_get('https://graph.microsoft.com/v1.0/sites/' . rawurlencode($siteid) . '/drives');
        if ($data && !empty($data['value'])) {
            foreach ($data['value'] as $drive) {
                if (isset($drive['name']) && strcasecmp($drive['name'], $this->libraryname) === 0) {
                    return $drive['id'];
                }
            }
        }
        return false;
    }

    /**
     * Listet Video-Dateien der konfigurierten Bibliothek, optional gefiltert nach Suchbegriff
     * (Dateiname/Inhalt, Graph-Volltextsuche). Nicht rekursiv (nur oberste Ebene der Bibliothek)
     * - deckt den typischen Fall ab, dass Videos direkt in einer Bibliothek liegen statt in
     * verschachtelten Unterordnern.
     *
     * @param int $page 1-basiert
     * @param int $perpage
     * @param string $search
     * @return array|false ['videos' => [...], 'total' => int]
     */
    public function list_videos($page = 1, $perpage = 25, $search = '') {
        $driveid = $this->resolve_drive_id();
        if (!$driveid) {
            return false;
        }

        if ($search !== '') {
            // Graph-Syntax für eine In-Bibliothek-Suche, siehe
            // learn.microsoft.com/graph/api/driveitem-search.
            $url = 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveid)
                . "/root/search(q='" . rawurlencode($search) . "')?\$top=200&\$expand=thumbnails";
        } else {
            $url = 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveid)
                . '/root/children?$top=200&$expand=thumbnails';
        }

        $items = [];
        // Über alle Seiten (@odata.nextLink) iterieren, sonst wäre eine Bibliothek mit mehr als
        // 200 Dateien beim ungefilterten Laden nicht vollständig sichtbar. $guard begrenzt dies
        // defensiv auf max. 2000 Elemente insgesamt.
        $guard = 0;
        while ($url && $guard < 10) {
            $data = $this->graph_get($url);
            if (!$data) {
                break;
            }
            if (!empty($data['value'])) {
                $items = array_merge($items, $data['value']);
            }
            $url = $data['@odata.nextLink'] ?? null;
            $guard++;
        }

        // Nur tatsächlich als Video erkannte Dateien - Graph liefert die "video"-Facette nur für
        // solche Elemente (learn.microsoft.com/graph/api/resources/video).
        $videos = array_values(array_filter($items, function ($item) {
            return isset($item['video']) && empty($item['folder']);
        }));

        $total = count($videos);
        $offset = max(0, ($page - 1) * $perpage);
        $pageitems = array_slice($videos, $offset, $perpage);

        $result = [];
        foreach ($pageitems as $item) {
            $thumbnail = $item['thumbnails'][0]['medium']['url']
                ?? $item['thumbnails'][0]['small']['url']
                ?? '';

            $result[] = [
                // driveId und itemId zusammen kodiert ("driveId|itemId") - die generische
                // Videoauswahl-UI in section_form.php kennt nur ein einzelnes ID-Feld je
                // Anbieter, Graph braucht aber immer beide zur eindeutigen Identifikation eines
                // Elements (anders als eine einzelne numerische Vimeo-/ignite-Video-ID).
                'id' => $driveid . '|' . $item['id'],
                'name' => $item['name'] ?? '',
                'description' => '',
                'thumbnail' => $thumbnail,
                // video.duration ist in Millisekunden angegeben, alle übrigen Stellen dieses
                // Plugins (Vimeo/ignite/DB-Feld "duration") rechnen konsistent in Sekunden.
                'duration' => isset($item['video']['duration']) ? (int) round($item['video']['duration'] / 1000) : 0,
                'created_time' => $item['createdDateTime'] ?? null,
            ];
        }

        return ['videos' => $result, 'total' => $total];
    }

    /**
     * Löst für ein Element eine frisch signierte, zeitlich begrenzte Download-Adresse auf.
     * Aufruf von /content OHNE automatisches Folgen der Weiterleitung: Graph beantwortet diesen
     * Endpunkt mit HTTP 302 auf eine vorsignierte, Range-Request-fähige Adresse, die selbst KEINE
     * Authentifizierung mehr braucht (siehe learn.microsoft.com/graph/api/driveitem-get-content).
     * Der eigentliche Dateiinhalt läuft dadurch nie über unseren PHP-Server (siehe
     * sharepoint_stream.php, das den Browser direkt zu dieser Adresse weiterleitet).
     *
     * @param string $driveid
     * @param string $itemid
     * @return string|false
     */
    public function get_download_url($driveid, $itemid) {
        $token = $this->get_token();
        if (!$token) {
            return false;
        }

        $url = 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveid)
            . '/items/' . rawurlencode($itemid) . '/content';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 300 && $httpcode < 400 && preg_match('/^Location:\s*(\S+)/mi', (string) $response, $m)) {
            return trim($m[1]);
        }
        return false;
    }

    /**
     * Verbindungstest für die Einstellungsseite (siehe test_api.php): prüft Token-Erwerb, Site-
     * und Bibliotheks-Auflösung nacheinander, damit ein Fehlschlag konkret einer dieser drei
     * Stufen zugeordnet werden kann statt nur "es funktioniert nicht".
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function test_connection() {
        if (!$this->configured()) {
            return ['success' => false, 'message' => get_string('api_test_no_credentials', 'local_railvieweradmin')];
        }
        if (!$this->get_token()) {
            return ['success' => false, 'message' => get_string('sharepoint_test_token_failed', 'local_railvieweradmin')];
        }
        $siteid = $this->resolve_site_id();
        if (!$siteid) {
            return ['success' => false, 'message' => get_string('sharepoint_test_site_failed', 'local_railvieweradmin')];
        }
        $driveid = $this->resolve_drive_id();
        if (!$driveid) {
            return ['success' => false, 'message' => get_string('sharepoint_test_drive_failed', 'local_railvieweradmin')];
        }
        return ['success' => true, 'message' => get_string('api_test_success_sharepoint', 'local_railvieweradmin')];
    }
}
