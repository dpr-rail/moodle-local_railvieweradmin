<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/ignite_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$id = required_param('id', PARAM_ALPHANUMEXT);

header('Content-Type: application/json');

$api = new \local_railvieweradmin\ignite_api();
$video = $api->get_video($id);

if ($video) {
    echo json_encode([
        'success' => true,
        'status' => $video['status'] ?? null,
        'statusProgress' => isset($video['statusProgress']) ? (int) $video['statusProgress'] : null,
        'errorCode' => $video['errorCode'] ?? null,
        'errorMessage' => $video['errorMessage'] ?? null,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
