<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('local/railvieweradmin:editsections', context_system::instance());
require_sesskey();

$prefix = optional_param('prefix', '', PARAM_RAW_TRIMMED);
$token = optional_param('token', '', PARAM_RAW_TRIMMED);

header('Content-Type: application/json');
$client = new \local_railvieweradmin\s3_client();
try {
    if (!$client->is_configured()) {
        throw new moodle_exception('s3_not_configured', 'local_railvieweradmin');
    }
    $res = $client->list_videos($prefix, $token);
    echo json_encode(['success' => true, 'objects' => $res['objects'], 'next' => $res['next']]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
