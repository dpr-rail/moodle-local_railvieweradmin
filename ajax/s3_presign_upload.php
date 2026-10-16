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

$filename = required_param('filename', PARAM_FILE);

header('Content-Type: application/json');
$client = new \local_railvieweradmin\s3_client(true);
$key = 'railviewer/' . time() . '_' . $filename;
if (!$client->is_configured() || !\local_railvieweradmin\s3_client::is_valid_key($key)) {
    echo json_encode(['success' => false, 'error' => get_string('s3_upload_invalid', 'local_railvieweradmin')]);
    exit;
}
echo json_encode(['success' => true, 'key' => $key, 'url' => $client->presign_put($key)]);
