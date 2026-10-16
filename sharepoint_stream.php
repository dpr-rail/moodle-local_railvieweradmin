<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/sharepoint_api.php');
require_login();

global $DB, $USER;

$sectionid = required_param('sectionid', PARAM_INT);

$section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);

if (($section->video_source ?? '') !== 'sharepoint'
        || empty($section->sharepoint_drive_id) || empty($section->sharepoint_item_id)) {
    send_file_not_found();
}

// Gleiche Zugriffsprüfung wie beim Ausliefern hochgeladener Videos über pluginfile.php
// (local_railvieweradmin_pluginfile()/local_railvieweradmin_user_can_access_section()) - Admins
// sowie Nutzer, denen eine Strecke zugewiesen wurde, die diesen Abschnitt enthält. Ohne diese
// Prüfung könnte jeder eingeloggte Moodle-Nutzer beliebige SharePoint-Videos dieses Plugins
// abrufen, unabhängig von einer tatsächlichen Zuweisung.
if (!local_railvieweradmin_user_can_access_section($sectionid, $USER->id)) {
    send_file_not_found();
}

$api = new \local_railvieweradmin\sharepoint_api();
$downloadurl = $api->get_download_url($section->sharepoint_drive_id, $section->sharepoint_item_id);

if (!$downloadurl) {
    header('HTTP/1.1 502 Bad Gateway');
    header('Content-Type: text/plain; charset=utf-8');
    echo get_string('sharepoint_stream_error', 'local_railvieweradmin');
    exit;
}

// Umleitung statt Proxy: der Browser lädt die eigentlichen Video-Bytes (inkl. HTTP-Range-
// Requests beim Vor-/Zurückspulen, von der signierten Adresse selbst unterstützt) direkt von
// dort, nicht über unseren PHP-Server - der müsste sonst potenziell mehrere GB pro Wiedergabe
// durchreichen (siehe ausführlicher Kommentar in classes/sharepoint_api.php::get_download_url()).
// Die signierte Adresse ist zeitlich begrenzt gültig; jeder erneute Aufruf dieser Seite (z.B.
// beim Laden eines neuen Abschnitts in play.php) fordert eine frische an - daher hier bewusst
// nicht browserseitig cachen.
header('Cache-Control: private, no-store, max-age=0');
header('Location: ' . $downloadurl);
exit;
