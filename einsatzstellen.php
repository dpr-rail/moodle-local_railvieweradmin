<?php
require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:manageeinsatzstellen', $context);

global $DB;

$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid) {
    require_sesskey();
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('railviewer_einsatz_routes', ['einsatzstelleid' => $deleteid]);
    $DB->delete_records('railviewer_einsatzstellen', ['id' => $deleteid]);
    $transaction->allow_commit();
    redirect(new moodle_url('/local/railvieweradmin/einsatzstellen.php'),
        get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/einsatzstellen.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_einsatzstellen', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('manage_einsatzstellen', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_einsatzstellen', 'local_railvieweradmin'));

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/railvieweradmin/index.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');

echo html_writer::link(new moodle_url('edit_einsatzstelle.php'), get_string('create_new_einsatzstelle', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::empty_tag('br');

$einsatzstellen = $DB->get_records('railviewer_einsatzstellen', null, 'name ASC');

if (empty($einsatzstellen)) {
    echo html_writer::div(get_string('no_einsatzstellen_yet', 'local_railvieweradmin'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('name', 'local_railvieweradmin'),
        get_string('description', 'local_railvieweradmin'),
        get_string('route_sections_col', 'local_railvieweradmin'),
        get_string('actions', 'local_railvieweradmin'),
    ];

    foreach ($einsatzstellen as $es) {
        $routes = $DB->get_records_sql(
            "SELECT r.name FROM {railviewer_einsatz_routes} er
              JOIN {railviewer_routes} r ON r.id = er.routeid
             WHERE er.einsatzstelleid = ?
          ORDER BY er.sortorder",
            [$es->id]
        );
        $routenames = implode(', ', array_map(function($r) { return format_string($r->name); }, $routes));

        $editurl = new moodle_url('edit_einsatzstelle.php', ['id' => $es->id]);
        $deleteurl = new moodle_url('einsatzstellen.php', ['delete' => $es->id, 'sesskey' => sesskey()]);
        $actions = html_writer::link($editurl, get_string('edit', 'local_railvieweradmin')) . ' | ' .
                   html_writer::link($deleteurl, get_string('delete', 'local_railvieweradmin'), [
                       'onclick' => "return confirm('" . get_string('confirm_delete', 'local_railvieweradmin') . "');",
                   ]);

        $table->data[] = [format_string($es->name), format_text($es->description), $routenames, $actions];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
