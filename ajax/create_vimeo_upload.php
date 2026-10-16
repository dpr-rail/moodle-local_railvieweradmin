<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/vimeo_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);
require_sesskey();

header('Content-Type: application/json');

// Legt nur das Video-Objekt bei Vimeo an und liefert den TUS-Upload-Link zurück - die Datei-
// Bytes selbst gehen NICHT durch diesen Endpunkt, sondern per TUS direkt vom Browser an Vimeo
// (siehe vimeo_api::create_upload() für die Begründung: sonst unbrauchbar für große Dateien).
$title = optional_param('title', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$filesize = optional_param('filesize', 0, PARAM_INT);
// 'anybody' (öffentlich), 'unlisted' oder 'nobody' (privat) - Vimeos eigene privacy.view-Werte.
$privacy = optional_param('privacy', 'anybody', PARAM_ALPHA);
if (!in_array($privacy, ['anybody', 'unlisted', 'nobody'], true)) {
    $privacy = 'anybody';
}

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => get_string('upload_title_required', 'local_railvieweradmin')]);
    exit;
}

if ($filesize <= 0) {
    echo json_encode(['success' => false, 'message' => get_string('upload_no_file', 'local_railvieweradmin')]);
    exit;
}

$api = new \local_railvieweradmin\vimeo_api();
$result = $api->create_upload($title, $description, $filesize, $privacy);
echo json_encode($result);
