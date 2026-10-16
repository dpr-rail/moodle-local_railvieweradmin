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
require_capability('local/railvieweradmin:assignroutes', $context);

$id = optional_param('id', 0, PARAM_INT);
// Bewusst NICHT "userid" genannt: das Formularfeld selbst heißt "userid" und wird bei einer
// neuen Zuweisung als Mehrfachauswahl per POST als Array (userid[]) übermittelt. Gleicher Name
// hier hätte beim Speichern dazu geführt, dass dieses optional_param() das POST-Array statt
// eines Skalars zurückgibt, was moodle_url weiter unten mit "Url parameters values can not be
// arrays!" quittiert.
$presetuserid = optional_param('presetuserid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_assignment.php', ['id' => $id, 'presetuserid' => $presetuserid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_assignment_title', 'local_railvieweradmin') : get_string('create_new_assignment', 'local_railvieweradmin'));
$PAGE->set_heading($id ? get_string('edit_assignment_title', 'local_railvieweradmin') : get_string('create_new_assignment', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_assignments', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/assignments.php'));
$PAGE->navbar->add($id ? get_string('edit_assignment_title', 'local_railvieweradmin') : get_string('create_new_assignment', 'local_railvieweradmin'));

echo $OUTPUT->header();

global $DB;

require_once(__DIR__ . '/assign_review.php');

// Lernmethoden-Feature, Schritt 2: bestätigte Streckenliste anwenden (Zusammenführen/Erlassen).
if (\local_railvieweradmin\learning::enabled() && optional_param('step2', 0, PARAM_INT)) {
    require_sesskey();
    $userids = required_param_array('userids', PARAM_INT);
    $routeids = required_param_array('routeids', PARAM_INT);
    $typeid = required_param('experiencetypeid', PARAM_INT);
    $einsatzstelleid = optional_param('einsatzstelleid', 0, PARAM_INT);
    $keep = optional_param_array('keep', [], PARAM_INT);
    $reasons = optional_param_array('reason', [], PARAM_TEXT);

    $errors = [];
    foreach ($routeids as $rid) {
        if (empty($keep[$rid]) && trim($reasons[$rid] ?? '') === '') {
            $errors[] = get_string('waive_reason_required', 'local_railvieweradmin',
                format_string($DB->get_field('railviewer_routes', 'name', ['id' => $rid])));
        }
    }
    if ($errors) {
        local_railvieweradmin_render_assign_review($userids, $routeids, $typeid, $einsatzstelleid, $errors, $keep, $reasons);
        echo $OUTPUT->footer();
        exit;
    }

    $counts = ['created' => 0, 'merged' => 0, 'exists' => 0];
    $transaction = $DB->start_delegated_transaction();
    foreach ($routeids as $rid) {
        $waived = empty($keep[$rid]);
        foreach ($userids as $uid) {
            $res = \local_railvieweradmin\learning::merge_assignment((int) $uid, (int) $rid, (int) $typeid, 1,
                $waived, trim($reasons[$rid] ?? ''), (int) $USER->id, $einsatzstelleid ?: null);
            $counts[$res['status']]++;
        }
    }
    $transaction->allow_commit();
    redirect(new moodle_url('assignments.php'), get_string('assignments_applied', 'local_railvieweradmin', (object) $counts),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($id) {
    $assignment = $DB->get_record('railviewer_assignments', ['id' => $id], '*', MUST_EXIST);
} else {
    $assignment = new stdClass();
    $assignment->id = 0;
    // Neue Zuweisung: userid ist hier ein Array, weil das Formular bei einer neuen Zuweisung
    // eine Mehrfachauswahl erlaubt (siehe assignment_form.php). Über ?presetuserid=X vorbelegt
    // (z.B. vom "Diesem User Strecken zuweisen"-Button auf assignments.php), sonst leer.
    $assignment->userid = $presetuserid ? [$presetuserid] : [];
    // Neue Zuweisung: routeid ist hier ein Array, weil das Formular bei einer neuen Zuweisung
    // eine Mehrfachauswahl erlaubt (siehe assignment_form.php).
    $assignment->routeid = [];
    $assignment->einsatzstelleid = 0;
    $assignment->required_views = 1;
}

// Formular erzeugen, Daten übergeben
$mform = new \local_railvieweradmin\forms\assignment_form(null, ['data' => $assignment]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('assignments.php'));
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        // Bestehende Zuweisung bearbeiten: weiterhin genau ein Nutzer, die Zeile existiert schon.
        $DB->update_record('railviewer_assignments', $data);
        redirect(new moodle_url('assignments.php'), get_string('assignment_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Neue Zuweisung(en): pro ausgewähltem Nutzer und pro Strecke eine eigene Zeile
        // anlegen. Nutzer, die für eine Strecke schon eine Zuweisung haben, werden für genau
        // diese Strecke übersprungen statt dupliziert.
        $userids = is_array($data->userid) ? $data->userid : [$data->userid];

        // Zielstrecken sammeln: manuell ausgewählte Strecken UND alle Strecken/Orte einer
        // gewählten Einsatzstelle, dedupliziert (Reihenfolge irrelevant, es folgt nur eine
        // ungeordnete Menge zu erstellender Zuweisungen).
        $routeidstoassign = \local_railvieweradmin\learning::resolve_routes(
            !empty($data->routeid) ? (array) $data->routeid : [],
            (int) ($data->einsatzstelleid ?? 0),
            !empty($data->assignbothdirections));

        // Lernmethoden-Feature: erst Schritt 2 (Prüfen/Abwählen), dort wird zugewiesen.
        if (\local_railvieweradmin\learning::enabled()) {
            local_railvieweradmin_render_assign_review(array_map('intval', $userids), $routeidstoassign,
                (int) $data->experiencetypeid, (int) ($data->einsatzstelleid ?? 0));
            echo $OUTPUT->footer();
            exit;
        }

        $created = 0;
        $skipped = 0;
        foreach ($routeidstoassign as $targetrouteid) {
            foreach ($userids as $selecteduserid) {
                if ($DB->record_exists('railviewer_assignments', ['userid' => $selecteduserid, 'routeid' => $targetrouteid])) {
                    $skipped++;
                    continue;
                }
                $record = clone $data;
                unset($record->id, $record->assignbothdirections, $record->einsatzstelleid);
                $record->userid = $selecteduserid;
                $record->routeid = $targetrouteid;
                $DB->insert_record('railviewer_assignments', $record);
                $created++;
            }
        }

        if ($created > 0 && $skipped > 0) {
            $message = get_string('assignments_created_some_skipped', 'local_railvieweradmin', (object) ['created' => $created, 'skipped' => $skipped]);
        } else if ($created === 0 && $skipped > 0) {
            $message = get_string('assignments_all_skipped', 'local_railvieweradmin');
        } else {
            $message = get_string('assignment_saved', 'local_railvieweradmin');
        }
        redirect(new moodle_url('assignments.php'), $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$mform->set_data($assignment);
$mform->display();

echo $OUTPUT->footer();

