<?php
require_once(__DIR__.'/../../../config.php');
require_login();

$file = __DIR__ . '/../geodata/nodes.geojson';
if (!is_readable($file)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: private, max-age=3600');
readfile($file);
