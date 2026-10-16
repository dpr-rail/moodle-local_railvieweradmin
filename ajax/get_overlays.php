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

$sectionid = required_param('sectionid', PARAM_INT);

header('Content-Type: application/json; charset=utf-8');

try {
    global $DB;
    
    // Overlays für den Abschnitt abrufen, absteigend nach Startzeit sortiert
    $overlays = $DB->get_records('railviewer_overlays', 
                                ['sectionid' => $sectionid], 
                                'endtime DESC, id DESC');
    
    // Daten für JSON vorbereiten
    $overlay_data = [];
    foreach ($overlays as $overlay) {
        $overlay_data[] = [
            'id' => (int)$overlay->id,
            'name' => $overlay->name,
            'starttime' => (float)$overlay->starttime,
            'endtime' => (float)$overlay->endtime,
            'posx' => (float)$overlay->posx,
            'posy' => (float)$overlay->posy,
            'fontsize' => isset($overlay->fontsize) ? (int)$overlay->fontsize : 12,
            'fontcolor' => $overlay->fontcolor ?? '#000000',
            'backgroundcolor' => $overlay->backgroundcolor ?? '#ffffff',
            'bordercolor' => $overlay->bordercolor ?? '#000000',
            'fixedboxwidth' => isset($overlay->fixedboxwidth) ? (int)$overlay->fixedboxwidth : null,
            'markerposition' => $overlay->markerposition ?? 'none',
        ];
    }

    
    echo json_encode([
        'success' => true,
        'overlays' => $overlay_data,
        'count' => count($overlay_data)
    ]);
    
} catch (Exception $e) {
    debugging('ajax/get_overlays.php: ' . $e->getMessage(), DEBUG_NORMAL);
    echo json_encode([
        'success' => false,
        'message' => get_string('generic_ajax_error', 'local_railvieweradmin')
    ]);
}
