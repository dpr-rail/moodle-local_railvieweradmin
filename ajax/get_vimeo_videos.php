<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Part of the local_railvieweradmin plugin.
 *
 * @package    local_railvieweradmin
 * @copyright  dpr-rail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
