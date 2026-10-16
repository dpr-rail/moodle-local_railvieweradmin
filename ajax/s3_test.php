<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('local/railvieweradmin:managesettings', context_system::instance());
require_sesskey();

header('Content-Type: application/json');
$result = (new \local_railvieweradmin\s3_client())->test_connection();
echo json_encode([
    'success' => $result === true,
    'message' => $result === true ? get_string('s3_test_ok', 'local_railvieweradmin') : $result,
]);
