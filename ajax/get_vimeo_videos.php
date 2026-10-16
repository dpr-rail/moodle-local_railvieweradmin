<?php
require_once(__DIR__.'/../../../config.php');
require_once('../classes/vimeo_api.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$page = optional_param('page', 1, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

header('Content-Type: application/json');

$vimeo_api = new \local_railvieweradmin\vimeo_api();

if (!empty($search)) {
    $result = $vimeo_api->search_videos($search, $page);
} else {
    $result = $vimeo_api->get_user_videos($page);
}

if ($result) {
    $videos = [];
    foreach ($result['data'] as $video) {
        $videos[] = [
            'id' => str_replace('/videos/', '', $video['uri']),
            'name' => $video['name'],
            'description' => $video['description'],
            'thumbnail' => $video['pictures']['sizes'][2]['link'] ?? '',
            'duration' => $video['duration'],
            'created_time' => $video['created_time']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'total' => $result['total'],
        'page' => $result['page'],
        'per_page' => $result['per_page']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
