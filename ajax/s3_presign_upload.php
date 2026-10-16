<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('local/railvieweradmin:editsections', context_system::instance());
require_sesskey();

$filename = required_param('filename', PARAM_FILE);

header('Content-Type: application/json');
$client = new \local_railvieweradmin\s3_client(true);
$key = 'railviewer/' . time() . '_' . $filename;
if (!$client->is_configured() || !\local_railvieweradmin\s3_client::is_valid_key($key)) {
    echo json_encode(['success' => false, 'error' => get_string('s3_upload_invalid', 'local_railvieweradmin')]);
    exit;
}
echo json_encode(['success' => true, 'key' => $key, 'url' => $client->presign_put($key)]);
