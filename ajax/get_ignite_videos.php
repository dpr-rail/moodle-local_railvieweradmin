<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/ignite_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$page = optional_param('page', 1, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

header('Content-Type: application/json');

$api = new \local_railvieweradmin\ignite_api();

if (!empty($search)) {
    $result = $api->search_videos($search, $page);
} else {
    $result = $api->get_user_videos($page);
}

// Zulässige Status/Sichtbarkeit aus den Plugin-Einstellungen statt hart verdrahtet (siehe
// settings.php: ignite_allowed_statuses/ignite_show_private). admin_setting_configmulticheckbox
// speichert als Kommaliste, get_config() liefert false statt "", solange nie gespeichert wurde.
$allowedstatusesraw = get_config('local_railvieweradmin', 'ignite_allowed_statuses');
$allowedstatuses = $allowedstatusesraw ? explode(',', $allowedstatusesraw) : ['COMPLETE'];

// 'public'/'private'/'both' (siehe settings.php). Der Setting-Key heißt aus historischen
// Gründen weiterhin "ignite_show_private" (früher eine einfache Ja/Nein-Checkbox) - die
// Fallback-Zuordnung unten fängt einen eventuell noch aus dieser Checkbox-Zeit gespeicherten
// Wert (leer/"0"/"1") ab, damit eine bereits konfigurierte Instanz nicht plötzlich alle Videos
// verliert.
$visibilityfilterraw = get_config('local_railvieweradmin', 'ignite_show_private');
if ($visibilityfilterraw === 'public' || $visibilityfilterraw === 'private' || $visibilityfilterraw === 'both') {
    $visibilityfilter = $visibilityfilterraw;
} else if ($visibilityfilterraw === '1') {
    $visibilityfilter = 'both';
} else {
    $visibilityfilter = 'public';
}

if ($result && isset($result['docs']) && is_array($result['docs'])) {
    $videos = [];
    foreach ($result['docs'] as $video) {
        // "visibility" ist das Feld hinter dem "Aktiviert/Deaktiviert"-Schalter in der
        // ignite.video-Oberfläche (public/private, siehe
        // docs.ignite.video/api-reference/videos/object).
        if (!in_array($video['status'] ?? null, $allowedstatuses, true)) {
            continue;
        }
        $visibility = $video['visibility'] ?? null;
        if ($visibilityfilter === 'public' && $visibility !== 'public') {
            continue;
        }
        if ($visibilityfilter === 'private' && $visibility === 'public') {
            continue;
        }
        // 'both': keine Sichtbarkeits-Filterung.

        // "previewThumbnailUrlSmall" ist eine direkt nutzbare, vorsignierte URL - robuster als
        // src.thumbnails[N].formats.<format>.url, das hier fälschlich als src.thumbnails[N].url
        // (ohne "formats"-Zwischenebene) gelesen wurde und deshalb immer leer war.
        $thumbnail = $video['previewThumbnailUrlSmall'] ?? $video['previewThumbnailUrl'] ?? '';

        $videos[] = [
            'id' => (string)($video['id'] ?? ''),
            'name' => $video['title'] ?? '',
            'description' => $video['description'] ?? '',
            'thumbnail' => $thumbnail,
            'duration' => $video['duration'] ?? 0,
            'created_time' => $video['createdAt'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'videos' => $videos,
        // total/per_page beziehen sich weiterhin auf die ungefilterte Seite von der API (nicht
        // neu paginiert) - bei wenigen deaktivierten/noch kodierenden Videos vernachlässigbar,
        // eine exakte Nachpaginierung wäre für diesen schmalen Fall unverhältnismäßig.
        'total' => $result['totalDocs'] ?? count($videos),
        'page' => $result['page'] ?? $page,
        'per_page' => $result['limit'] ?? 25,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
