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
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

header('Content-Type: application/json');

global $DB;

$ril100 = required_param('ril100', PARAM_ALPHANUMEXT);

$section = $DB->get_record('railviewer_sections', [
    'location_type' => 'node',
    'node_ril100' => $ril100,
]);

if ($section) {
    echo json_encode([
        'status' => 'exists',
        'sectionid' => (int) $section->id,
        'url' => (new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $section->id]))->out(false),
    ]);
} else {
    echo json_encode(['status' => 'new']);
}
