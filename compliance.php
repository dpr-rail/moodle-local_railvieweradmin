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

// Soll-Matrix (Strecken x Einsatzstellen x Lernmethoden) als Vorschau und PDF-Download.
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:viewprogress', $context);

$self = new moodle_url('/local/railvieweradmin/compliance.php');
$PAGE->set_context($context);
$PAGE->set_url($self);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('compliance_requirements_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('compliance_requirements_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('compliance_requirements_title', 'local_railvieweradmin'));

global $DB;
if (!\local_railvieweradmin\learning::enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('learning_feature_disabled', 'local_railvieweradmin'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$typeids = optional_param_array('typeids', [], PARAM_INT);
$einsatzids = optional_param_array('einsatzids', [], PARAM_INT);

if (optional_param('download', 0, PARAM_INT)) {
    require_sesskey();
    $pdf = \local_railvieweradmin\compliance_report::render_requirements_pdf($typeids, $einsatzids);
    send_file($pdf, 'compliance_anforderungen_' . date('Ymd') . '.pdf', 0, 0, true, true, 'application/pdf');
    exit;
}

echo $OUTPUT->header();
$types = \local_railvieweradmin\learning::experience_types();
$einsatz = $DB->get_records_menu('railviewer_einsatzstellen', null, 'name ASC', 'id, name');

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $self->out(false)]);
echo html_writer::tag('p', get_string('compliance_filter_desc', 'local_railvieweradmin'));
$opts = [];
foreach ($types as $t) {
    $opts[$t->id] = format_string($t->name);
}
echo html_writer::div(html_writer::label(get_string('experience_types', 'local_railvieweradmin'), 'f-types') .
    html_writer::select($opts, 'typeids[]', $typeids, false, ['multiple' => 'multiple', 'id' => 'f-types', 'class' => 'form-control', 'size' => 4]), 'form-group');
$eopts = [];
foreach ($einsatz as $id => $name) {
    $eopts[$id] = format_string($name);
}
echo html_writer::div(html_writer::label(get_string('einsatzstellen', 'local_railvieweradmin'), 'f-es') .
    html_writer::select($eopts, 'einsatzids[]', $einsatzids, false, ['multiple' => 'multiple', 'id' => 'f-es', 'class' => 'form-control', 'size' => 6]), 'form-group');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary', 'value' => get_string('compliance_preview', 'local_railvieweradmin')]);
echo ' ';
// moodle_url erlaubt keine Array-Werte: Mehrfachauswahl als typeids[0]=…, einsatzids[0]=… übergeben.
$dlparams = ['download' => 1, 'sesskey' => sesskey()];
foreach (array_values($typeids) as $i => $v) {
    $dlparams["typeids[$i]"] = $v;
}
foreach (array_values($einsatzids) as $i => $v) {
    $dlparams["einsatzids[$i]"] = $v;
}
$dl = new moodle_url($self, $dlparams);
echo html_writer::link($dl, get_string('compliance_download_pdf', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

$selected = $typeids ? array_intersect_key($types, array_flip($typeids)) : $types;
foreach ($selected as $type) {
    echo html_writer::div(\local_railvieweradmin\compliance_report::requirements_html($type, $einsatzids), 'mt-4',
        ['style' => 'overflow-x:auto']);
}
echo $OUTPUT->footer();
