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
require_once(__DIR__.'/lib.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_route.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_route', 'local_railvieweradmin') : get_string('create_new_route', 'local_railvieweradmin'));
$PAGE->set_heading($id ? get_string('edit_route', 'local_railvieweradmin') : get_string('create_new_route', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_routes_title', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/routes.php'));
$PAGE->navbar->add($id ? get_string('edit_route', 'local_railvieweradmin') : get_string('create_new_route', 'local_railvieweradmin'));

echo $OUTPUT->header();

global $DB;

if ($id) {
    $route = $DB->get_record('railviewer_routes', ['id' => $id], '*', MUST_EXIST);
    $sectionsids = $DB->get_records('railviewer_route_sections', ['routeid' => $id], 'sortorder ASC');
    $selectedsections = array_map(function($rec) { return $rec->sectionid; }, $sectionsids);
} else {
    $route = new stdClass();
    $route->id = 0;
    $route->name = '';
    $route->description = '';
    $route->pairedrouteid = null;
    $route->direction = null;
    $selectedsections = [];
}

$mform = new \local_railvieweradmin\forms\route_form(null, [
    'selectedsections' => $selectedsections,
    'routeid' => $id,
    'currentpairedrouteid' => (int) ($route->pairedrouteid ?? 0),
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('routes.php'));
} else if ($data = $mform->get_data()) {
    $transaction = $DB->start_delegated_transaction();

    // pairedrouteid/direction NICHT über update_record() mitschreiben lassen - die
    // dedizierte Helper-Funktion unten ist der einzige Schreiber dieser zwei Spalten (siehe
    // deren Docblock), sonst würde hier ein noch nicht symmetrisch aufgelöster Zwischenwert
    // landen, bevor local_railvieweradmin_update_route_pairing() ihn wieder überschreibt.
    $newpairedrouteid = (int) $data->pairedrouteid;
    $newdirection = $newpairedrouteid ? $data->direction : null;
    unset($data->pairedrouteid, $data->direction);

    if ($data->id) {
        $DB->update_record('railviewer_routes', $data);
        $DB->delete_records('railviewer_route_sections', ['routeid' => $data->id]);
        foreach ($data->sections as $sortorder => $sectionid) {
            $DB->insert_record('railviewer_route_sections', (object)[
                'routeid' => $data->id,
                'sectionid' => $sectionid,
                'sortorder' => $sortorder
            ]);
        }
        $routeid = $data->id;
    } else {
        $routeid = $DB->insert_record('railviewer_routes', (object)[
            'name' => $data->name,
            'description' => $data->description
        ]);
        foreach ($data->sections as $sortorder => $sectionid) {
            $DB->insert_record('railviewer_route_sections', (object)[
                'routeid' => $routeid,
                'sectionid' => $sectionid,
                'sortorder' => $sortorder
            ]);
        }
    }

    local_railvieweradmin_update_route_pairing($routeid, $newpairedrouteid, $newdirection);

    $transaction->allow_commit();
    redirect(new moodle_url('routes.php'), get_string('route_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$dataform = new stdClass();
$dataform->id = $route->id;
$dataform->name = $route->name;
$dataform->description = $route->description;
$dataform->sections = $selectedsections;
$dataform->pairedrouteid = (int) ($route->pairedrouteid ?? 0);
$dataform->direction = $route->direction ?? '';

$mform->set_data($dataform);
$mform->display();

echo $OUTPUT->footer();

