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
