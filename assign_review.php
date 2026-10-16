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

/**
 * Schritt 2 der Zuweisung (Lernmethoden-Feature): aufgelöste Streckenliste prüfen, einzelne
 * Strecken mit Begründung abwählen ("erlassen"). Wird von edit_assignment.php eingebunden.
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param int[] $userids
 * @param int[] $routeids aufgelöste Zielstrecken
 * @param string[] $errors
 * @param int[] $keep routeid => 1 für behaltene Strecken (Fehlerfall: vorherige Auswahl)
 * @param string[] $reasons routeid => Begründung
 */
function local_railvieweradmin_render_assign_review(array $userids, array $routeids, int $typeid,
        int $einsatzstelleid, array $errors = [], ?array $keep = null, array $reasons = []) {
    global $DB, $OUTPUT;

    foreach ($errors as $e) {
        echo $OUTPUT->notification($e, 'error');
    }
    echo html_writer::tag('h3', get_string('assign_review_title', 'local_railvieweradmin'));
    echo html_writer::tag('p', get_string('assign_review_desc', 'local_railvieweradmin'));

    $type = $DB->get_record('railviewer_experience_types', ['id' => $typeid]);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('experience_type', 'local_railvieweradmin') . ': ')
        . s($type ? format_string($type->name) : '–'));

    $names = [];
    foreach ($DB->get_records_list('user', 'id', $userids, '', 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename') as $u) {
        $names[] = fullname($u);
    }
    echo html_writer::tag('p', html_writer::tag('strong', get_string('users', 'local_railvieweradmin') . ': ') . s(implode(', ', $names)));

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/railvieweradmin/edit_assignment.php'))->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'step2', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'experiencetypeid', 'value' => $typeid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'einsatzstelleid', 'value' => $einsatzstelleid]);
    foreach ($userids as $uid) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userids[]', 'value' => $uid]);
    }

    $table = new html_table();
    $table->head = [get_string('assign_keep', 'local_railvieweradmin'), get_string('route', 'local_railvieweradmin'),
        get_string('assign_existing_hint', 'local_railvieweradmin'), get_string('waive_reason', 'local_railvieweradmin')];
    foreach ($routeids as $rid) {
        $route = $DB->get_record('railviewer_routes', ['id' => $rid]);
        if (!$route) {
            continue;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['rid'] = $rid;
        $existing = $DB->count_records_select('railviewer_assignments', "routeid = :rid AND userid $insql", $params);
        $hint = $existing ? get_string('assign_existing_merge', 'local_railvieweradmin', $existing) : '–';
        $checked = $keep === null ? true : !empty($keep[$rid]);
        $table->data[] = [
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'routeids[]', 'value' => $rid]) .
            html_writer::checkbox("keep[$rid]", 1, $checked, ''),
            \local_railvieweradmin\learning::route_label($route),
            $hint,
            html_writer::empty_tag('input', ['type' => 'text', 'class' => 'form-control', 'name' => "reason[$rid]",
                'value' => $reasons[$rid] ?? '', 'placeholder' => get_string('waive_reason_placeholder', 'local_railvieweradmin')]),
        ];
    }
    echo html_writer::table($table);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('assign_confirm', 'local_railvieweradmin')]);
    echo ' ' . html_writer::link(new moodle_url('/local/railvieweradmin/assignments.php'), get_string('cancel'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_tag('form');
}
