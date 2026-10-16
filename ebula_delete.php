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
require_capability('local/railvieweradmin:deletesections', $context);
require_sesskey();

$id = required_param('id', PARAM_INT);

global $DB;

if (!$DB->record_exists('railviewer_ebulas', ['id' => $id])) {
    throw new moodle_exception('record_not_found', 'local_railvieweradmin');
}

// Löscht nur den Roh-Fahrplan selbst - bereits einem Abschnitt zugewiesene Ausschnitte
// (railviewer_ebula_section) bleiben unangetastet, die sind als fertiges HTML/JSON kopiert und
// benötigen die Roh-XML nicht mehr. Der Verweis (ebulaid) auf diesen dann gelöschten
// Fahrplan wird zu einer verwaisten ID, ohne dass das die Anzeige des Ausschnitts beeinträchtigt
// (siehe ebulas.php/index_ebulas.php - dort wird nicht gegen railviewer_ebulas zurückverknüpft).
$DB->delete_records('railviewer_ebulas', ['id' => $id]);

redirect(new moodle_url('/local/railvieweradmin/ebulas.php'),
    get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
