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
require_sesskey();

header('Content-Type: application/json');

// Legt nur das Video-Objekt bei ignite.video an und liefert die vorsignierte S3-Upload-URL
// zurück - die Datei-Bytes selbst gehen NICHT durch diesen Endpunkt, sondern per PUT direkt vom
// Browser an S3 (siehe ignite_api::create_upload() für die Begründung: sonst unbrauchbar für
// große Dateien).
$title = optional_param('title', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$visibility = optional_param('visibility', 'public', PARAM_ALPHA);
if (!in_array($visibility, ['public', 'private'], true)) {
    $visibility = 'public';
}

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => get_string('upload_title_required', 'local_railvieweradmin')]);
    exit;
}

$api = new \local_railvieweradmin\ignite_api();
$result = $api->create_upload($title, $description, $visibility);
echo json_encode($result);
