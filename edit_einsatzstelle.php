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
require_capability('local/railvieweradmin:manageeinsatzstellen', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_einsatzstelle.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_einsatzstelle', 'local_railvieweradmin') : get_string('create_new_einsatzstelle', 'local_railvieweradmin'));
$PAGE->set_heading($id ? get_string('edit_einsatzstelle', 'local_railvieweradmin') : get_string('create_new_einsatzstelle', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_einsatzstellen', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/einsatzstellen.php'));
$PAGE->navbar->add($id ? get_string('edit_einsatzstelle', 'local_railvieweradmin') : get_string('create_new_einsatzstelle', 'local_railvieweradmin'));

echo $OUTPUT->header();

global $DB;

if ($id) {
    $einsatzstelle = $DB->get_record('railviewer_einsatzstellen', ['id' => $id], '*', MUST_EXIST);
    $selectedroutes = $DB->get_fieldset_select('railviewer_einsatz_routes', 'routeid', 'einsatzstelleid = ?', [$id]);
} else {
    $einsatzstelle = new stdClass();
    $einsatzstelle->id = 0;
    $einsatzstelle->name = '';
    $einsatzstelle->description = '';
    $selectedroutes = [];
}

$mform = new \local_railvieweradmin\forms\einsatzstelle_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('einsatzstellen.php'));
} else if ($data = $mform->get_data()) {
    $transaction = $DB->start_delegated_transaction();
    $now = time();

    if ($data->id) {
        $DB->update_record('railviewer_einsatzstellen', (object) [
            'id' => $data->id,
            'name' => $data->name,
            'description' => $data->description,
            'timemodified' => $now,
        ]);
        $einsatzstelleid = $data->id;
        $DB->delete_records('railviewer_einsatz_routes', ['einsatzstelleid' => $einsatzstelleid]);
    } else {
        $einsatzstelleid = $DB->insert_record('railviewer_einsatzstellen', (object) [
            'name' => $data->name,
            'description' => $data->description,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    $routeids = is_array($data->routes) ? $data->routes : [$data->routes];
    foreach (array_values($routeids) as $sortorder => $routeid) {
        $DB->insert_record('railviewer_einsatz_routes', (object) [
            'einsatzstelleid' => $einsatzstelleid,
            'routeid' => $routeid,
            'sortorder' => $sortorder,
        ]);
    }

    $transaction->allow_commit();
    redirect(new moodle_url('einsatzstellen.php'), get_string('einsatzstelle_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$dataform = new stdClass();
$dataform->id = $einsatzstelle->id;
$dataform->name = $einsatzstelle->name;
$dataform->description = $einsatzstelle->description;
$dataform->routes = array_map('intval', $selectedroutes);

$mform->set_data($dataform);
$mform->display();

echo $OUTPUT->footer();
