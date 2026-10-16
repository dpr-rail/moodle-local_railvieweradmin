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
