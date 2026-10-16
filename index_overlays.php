<?php
require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/index_overlays.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('overlay_sections_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('overlay_sections_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('overlay_sections_title', 'local_railvieweradmin'));

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/railvieweradmin/index.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');

echo html_writer::link(new moodle_url('edit_overlay.php'), get_string('create_new_overlay', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::empty_tag('hr');

global $DB;

// Abschnitte mit Overlays zählen
$sql = "
    SELECT s.id, s.name, COUNT(o.id) AS overlaycount
    FROM {railviewer_sections} s
    JOIN {railviewer_overlays} o ON o.sectionid = s.id
    GROUP BY s.id, s.name
    ORDER BY s.name ASC
";

$records = $DB->get_records_sql($sql);

$table = new html_table();
$table->head = [
    get_string('section', 'local_railvieweradmin'),
    get_string('overlay_count_col', 'local_railvieweradmin'),
    get_string('actions', 'local_railvieweradmin'),
];

foreach ($records as $record) {
    $editurl = new moodle_url('index_section_overlays.php', ['sectionid' => $record->id]);
    $table->data[] = [
        format_string($record->name),
        $record->overlaycount,
        html_writer::link($editurl, get_string('edit', 'local_railvieweradmin'))
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();

