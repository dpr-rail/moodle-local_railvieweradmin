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

require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$sectionid = optional_param('sectionid', 0, PARAM_INT);

global $DB;

try {
    $points = $DB->get_records('railviewer_ebula_section_map', 
        ['sectionid' => $sectionid], 'videotime DeSC');
    
    $result = [];
    foreach ($points as $point) {
        $result[] = [
            'videotime' => $point->videotime,
            'line_top' => $point->line_top,
            'is_static' => $point->is_static
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'points' => $result]);
} catch (Exception $e) {
    debugging('ebula_section_map_get.php: ' . $e->getMessage(), DEBUG_NORMAL);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
