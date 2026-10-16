<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/ignite_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);
require_sesskey();

header('Content-Type: application/json');

// Legt nur das Video-Objekt bei ignite.video an und liefert die vorsignierte S3-Upload-URL
// zurück - die Datei-Bytes selbst gehen NICHT durch diesen Endpunkt, sondern per PUT direkt vom
// Browser an S3 (siehe ignite_api::create_upload() für die Begründung: sonst unbrauchbar für
// große Dateien).
$title = optional_param('title', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$visibility = optional_param('visibility', 'public', PARAM_ALPHA);
if (!in_array($visibility, ['public', 'private'], true)) {
    $visibility = 'public';
}

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => get_string('upload_title_required', 'local_railvieweradmin')]);
    exit;
}

$api = new \local_railvieweradmin\ignite_api();
$result = $api->create_upload($title, $description, $visibility);
echo json_encode($result);
