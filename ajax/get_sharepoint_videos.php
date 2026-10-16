<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/sharepoint_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$page = optional_param('page', 1, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

header('Content-Type: application/json');

$api = new \local_railvieweradmin\sharepoint_api();
$result = $api->list_videos($page, 25, $search);

if ($result !== false) {
    echo json_encode([
        'success' => true,
        'videos' => $result['videos'],
        'total' => $result['total'],
        'page' => $page,
        'per_page' => 25,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
