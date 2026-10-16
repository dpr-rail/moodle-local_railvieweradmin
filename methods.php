<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/methods.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('learning_methods', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('learning_methods', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('learning_methods', 'local_railvieweradmin'));

global $DB;

if (!\local_railvieweradmin\learning::enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('learning_feature_disabled', 'local_railvieweradmin'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$self = new moodle_url('/local/railvieweradmin/methods.php');

if ($action = optional_param('action', '', PARAM_ALPHA)) {
    require_sesskey();
    $id = optional_param('id', 0, PARAM_INT);
    if ($action === 'save') {
        $record = (object) [
            'name' => local_railvieweradmin_clean_label(optional_param('methodname', '', PARAM_RAW)),
            'night' => optional_param('night', 0, PARAM_BOOL),
            'sortorder' => optional_param('sortorder', 0, PARAM_INT),
            'enabled' => optional_param('enabled', 0, PARAM_BOOL),
        ];
        if ($record->name === '') {
            redirect($self, get_string('required_field', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_ERROR);
        }
        if ($id) {
            $record->id = $id;
            $DB->update_record('railviewer_methods', $record);
        } else {
            $base = preg_replace('/[^a-z0-9_]+/', '_', core_text::strtolower($record->name));
            $record->shortname = substr($base, 0, 40) . '_' . substr(md5(uniqid('', true)), 0, 6);
            $DB->insert_record('railviewer_methods', $record);
        }
        redirect($self, get_string('record_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'delete' && $id) {
        require_capability('local/railvieweradmin:deletesections', $context);
        // Nur löschen, wenn nirgends verwendet - sonst deaktivieren statt Daten zu verwaisen.
        $used = $DB->record_exists('railviewer_rides', ['methodid' => $id])
            || $DB->record_exists('railviewer_route_reqs', ['methodid' => $id])
            || $DB->record_exists('railviewer_assignment_req', ['methodid' => $id]);
        if ($used) {
            redirect($self, get_string('method_in_use', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_WARNING);
        }
        $DB->delete_records('railviewer_route_substs', ['methodid' => $id]);
        $DB->delete_records('railviewer_route_substs', ['bymethodid' => $id]);
        $DB->delete_records('railviewer_section_durations', ['methodid' => $id]);
        $DB->delete_records('railviewer_methods', ['id' => $id]);
        redirect($self, get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('learning_methods_desc', 'local_railvieweradmin'));

$editid = optional_param('edit', 0, PARAM_INT);
$edit = $editid ? $DB->get_record('railviewer_methods', ['id' => $editid]) : null;

$table = new html_table();
$table->head = [get_string('name', 'local_railvieweradmin'), get_string('method_night', 'local_railvieweradmin'),
    get_string('sortorder', 'local_railvieweradmin'), get_string('enabled', 'local_railvieweradmin'),
    get_string('actions', 'local_railvieweradmin')];
foreach (\local_railvieweradmin\learning::methods(false) as $m) {
    $deleteurl = new moodle_url($self, ['action' => 'delete', 'id' => $m->id, 'sesskey' => sesskey()]);
    $table->data[] = [
        format_string($m->name),
        $m->night ? get_string('yes') : get_string('no'),
        (int) $m->sortorder,
        $m->enabled ? get_string('yes') : get_string('no'),
        html_writer::link(new moodle_url($self, ['edit' => $m->id]), get_string('edit', 'local_railvieweradmin'))
            . local_railvieweradmin_delete_link($deleteurl, [
                'onclick' => 'return confirm(' . json_encode(get_string('confirm_delete', 'local_railvieweradmin')) . ');',
            ]),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', $edit ? get_string('edit', 'local_railvieweradmin') : get_string('add_method', 'local_railvieweradmin'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $self->out(false), 'class' => 'mform']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $edit->id ?? 0]);
echo html_writer::div(html_writer::label(get_string('name', 'local_railvieweradmin'), 'm-name') .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'methodname', 'id' => 'm-name', 'class' => 'form-control',
        'required' => 'required', 'value' => $edit->name ?? '']), 'form-group');
echo html_writer::div(html_writer::label(get_string('sortorder', 'local_railvieweradmin'), 'm-sort') .
    html_writer::empty_tag('input', ['type' => 'number', 'name' => 'sortorder', 'id' => 'm-sort', 'class' => 'form-control',
        'value' => $edit->sortorder ?? 0]), 'form-group');
echo html_writer::div(html_writer::checkbox('night', 1, !empty($edit->night), get_string('method_night', 'local_railvieweradmin')), 'form-group');
echo html_writer::div(html_writer::checkbox('enabled', 1, $edit ? !empty($edit->enabled) : true, get_string('enabled', 'local_railvieweradmin')), 'form-group');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
