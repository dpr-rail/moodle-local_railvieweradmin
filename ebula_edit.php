<?php
require_once(__DIR__.'/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$id = required_param('id', PARAM_INT);

global $DB;

$ebula = $DB->get_record('railviewer_ebulas', ['id' => $id], '*', MUST_EXIST);

$errors = [];

if (optional_param('submit', '', PARAM_TEXT)) {
    require_sesskey();
    $newxml = required_param('xml', PARAM_RAW);

    // Nur auf Wohlgeformtheit prüfen (nicht gegen das XSD-Schema wie beim erstmaligen
    // Zuweisen eines Ausschnitts in ebula_section_edit.php) - hier soll auch eine noch
    // unvollständige Korrektur zwischengespeichert werden können.
    libxml_use_internal_errors(true);
    $test = simplexml_load_string($newxml);
    if ($test === false) {
        $errors[] = get_string('xml_simplexml_load_error', 'local_railvieweradmin');
        foreach (libxml_get_errors() as $xmlerror) {
            $errors[] = s(trim($xmlerror->message));
        }
        libxml_clear_errors();
    } else {
        $DB->set_field('railviewer_ebulas', 'xml', $newxml, ['id' => $id]);
        redirect(new moodle_url('/local/railvieweradmin/ebulas.php'),
            get_string('ebula_updated', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/ebula_edit.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('edit_ebula', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('edit_ebula', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('ebula_timetables', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/ebulas.php'));
$PAGE->navbar->add(get_string('edit_ebula', 'local_railvieweradmin'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit_ebula', 'local_railvieweradmin'));

if (!empty($errors)) {
    echo html_writer::div(implode(html_writer::empty_tag('br'), $errors), 'alert alert-danger');
}

// Eine Änderung der Roh-XML wirkt sich NICHT rückwirkend auf bereits einem Abschnitt
// zugewiesene Ausschnitte aus (die sind als fertiges HTML/JSON kopiert, siehe
// ebula_section_save.php) - Hinweis, damit das nicht überrascht.
$linkedcount = $DB->count_records('railviewer_ebula_section', ['ebulaid' => $id]);
if ($linkedcount > 0) {
    echo html_writer::div(get_string('ebula_edit_linked_notice', 'local_railvieweradmin', $linkedcount),
        'alert alert-info');
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('ebula_xml_content', 'local_railvieweradmin'), ['for' => 'xml']);
echo html_writer::tag('textarea', s($ebula->xml), [
    'name' => 'xml',
    'id' => 'xml',
    'class' => 'form-control',
    'rows' => 25,
    'style' => 'font-family: monospace; font-size: 12px;',
]);
echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/railvieweradmin/ebulas.php'), get_string('cancel')),
    'mt-2'
);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
