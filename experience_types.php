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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/experience_types.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('experience_types', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('experience_types', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('experience_types', 'local_railvieweradmin'));

global $DB;

if (!\local_railvieweradmin\learning::enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('learning_feature_disabled', 'local_railvieweradmin'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$self = new moodle_url('/local/railvieweradmin/experience_types.php');

if ($action = optional_param('action', '', PARAM_ALPHA)) {
    require_sesskey();
    $id = optional_param('id', 0, PARAM_INT);
    if ($action === 'save') {
        $record = (object) [
            'name' => local_railvieweradmin_clean_label(optional_param('typename', '', PARAM_RAW)),
            'sortorder' => optional_param('sortorder', 0, PARAM_INT),
            'enabled' => optional_param('enabled', 0, PARAM_BOOL),
        ];
        if ($record->name === '') {
            redirect($self, get_string('required_field', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_ERROR);
        }
        if ($id) {
            $record->id = $id;
            $DB->update_record('railviewer_experience_types', $record);
        } else {
            $DB->insert_record('railviewer_experience_types', $record);
        }
        redirect($self, get_string('record_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'delete' && $id) {
        require_capability('local/railvieweradmin:deletesections', $context);
        if ($DB->record_exists('railviewer_assignments', ['experiencetypeid' => $id])
                || $DB->record_exists('railviewer_route_reqs', ['experiencetypeid' => $id])) {
            redirect($self, get_string('experience_type_in_use', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_WARNING);
        }
        $DB->delete_records('railviewer_experience_types', ['id' => $id]);
        redirect($self, get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('experience_types_desc', 'local_railvieweradmin'));

$editid = optional_param('edit', 0, PARAM_INT);
$edit = $editid ? $DB->get_record('railviewer_experience_types', ['id' => $editid]) : null;

$table = new html_table();
$table->head = [get_string('name', 'local_railvieweradmin'), get_string('sortorder', 'local_railvieweradmin'),
    get_string('enabled', 'local_railvieweradmin'), get_string('actions', 'local_railvieweradmin')];
foreach (\local_railvieweradmin\learning::experience_types(false) as $t) {
    $deleteurl = new moodle_url($self, ['action' => 'delete', 'id' => $t->id, 'sesskey' => sesskey()]);
    $table->data[] = [
        format_string($t->name), (int) $t->sortorder, $t->enabled ? get_string('yes') : get_string('no'),
        html_writer::link(new moodle_url($self, ['edit' => $t->id]), get_string('edit', 'local_railvieweradmin'))
            . local_railvieweradmin_delete_link($deleteurl, [
                'onclick' => 'return confirm(' . json_encode(get_string('confirm_delete', 'local_railvieweradmin')) . ');',
            ]),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', $edit ? get_string('edit', 'local_railvieweradmin') : get_string('add_experience_type', 'local_railvieweradmin'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $self->out(false), 'class' => 'mform']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $edit->id ?? 0]);
echo html_writer::div(html_writer::label(get_string('name', 'local_railvieweradmin'), 't-name') .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'typename', 'id' => 't-name', 'class' => 'form-control',
        'required' => 'required', 'value' => $edit->name ?? '']), 'form-group');
echo html_writer::div(html_writer::label(get_string('sortorder', 'local_railvieweradmin'), 't-sort') .
    html_writer::empty_tag('input', ['type' => 'number', 'name' => 'sortorder', 'id' => 't-sort', 'class' => 'form-control',
        'value' => $edit->sortorder ?? 0]), 'form-group');
echo html_writer::div(html_writer::checkbox('enabled', 1, $edit ? !empty($edit->enabled) : true, get_string('enabled', 'local_railvieweradmin')), 'form-group');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
