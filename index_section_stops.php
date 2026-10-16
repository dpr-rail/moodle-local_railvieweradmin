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
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$sectionid = required_param('sectionid', PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);

if ($deleteid) {
    require_sesskey();
    require_capability('local/railvieweradmin:deletesections', $context);
    $DB->delete_records('railviewer_stops', ['id' => $deleteid]);
    redirect(new moodle_url('/local/railvieweradmin/index_section_stops.php', ['sectionid' => $sectionid]),
        get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/index_section_stops.php', ['sectionid' => $sectionid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('stops_for_section', 'local_railvieweradmin', ''));
$PAGE->set_heading(get_string('stops_for_section', 'local_railvieweradmin', ''));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->navbar->add(get_string('stops_for_section', 'local_railvieweradmin', ''));

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/railvieweradmin/index_sections.php'), get_string('back_to_section_overview', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');

global $DB;

// Abschnitt laden
$section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);
echo $OUTPUT->heading(get_string('stops_for_section', 'local_railvieweradmin', format_string($section->name)));

// Button: Neuen Stop anlegen (mit vorausgewähltem Abschnitt)
$addurl = new moodle_url('/local/railvieweradmin/edit_stop.php', ['sectionid' => $sectionid]);
echo html_writer::link($addurl, get_string('add_new_stop', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::empty_tag('hr');

// Alle Stopps für diesen Abschnitt
$stops = $DB->get_records('railviewer_stops', ['sectionid' => $sectionid], 'stoptime ASC');

if ($stops) {
    $table = new html_table();
    $table->head = [
        get_string('stop_name', 'local_railvieweradmin'),
        get_string('time_of_stop_seconds', 'local_railvieweradmin'),
        get_string('actions', 'local_railvieweradmin'),
    ];

    foreach ($stops as $stop) {
        $editurl = new moodle_url('/local/railvieweradmin/edit_stop.php', ['id' => $stop->id]);
        $deleteurl = new moodle_url('/local/railvieweradmin/index_section_stops.php', ['sectionid' => $sectionid, 'delete' => $stop->id, 'sesskey' => sesskey()]);

        $actions = html_writer::link($editurl, get_string('edit', 'local_railvieweradmin')) . local_railvieweradmin_delete_link($deleteurl, [
                       'onclick' => "return confirm('" . get_string('confirm_delete', 'local_railvieweradmin') . "');",
                   ]);

        $table->data[] = [
            format_string($stop->name),
            format_float($stop->stoptime),
            $actions,
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_stops_for_section', 'local_railvieweradmin'));
}

echo $OUTPUT->footer();
