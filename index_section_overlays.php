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

// Löschen
if ($deleteid) {
    require_sesskey();
    require_capability('local/railvieweradmin:deletesections', $context);
    $DB->delete_records('railviewer_overlays', ['id' => $deleteid]);
    redirect(new moodle_url('/local/railvieweradmin/index_section_overlays.php', ['sectionid' => $sectionid]),
        get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

global $DB;

// Abschnitt laden
$section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);
$pagetitle = get_string('overlays_for_section', 'local_railvieweradmin', format_string($section->name));

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/index_section_overlays.php', ['sectionid' => $sectionid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('overlay_sections_title', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_overlays.php'));
$PAGE->navbar->add($pagetitle);

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/railvieweradmin/index_overlays.php'), get_string('overlay_sections_title', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');
echo $OUTPUT->heading($pagetitle);

// Button: Neues Overlay anlegen (mit vorausgewähltem Abschnitt)
$addurl = new moodle_url('/local/railvieweradmin/edit_overlay.php', ['sectionid' => $sectionid]);
echo html_writer::link($addurl, get_string('create_new_overlay', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::empty_tag('hr');

// Alle Overlays für diesen Abschnitt
$overlays = $DB->get_records('railviewer_overlays', ['sectionid' => $sectionid]);

if ($overlays) {
    $table = new html_table();
    $table->head = [
        get_string('overlay_name', 'local_railvieweradmin'),
        get_string('start_time', 'local_railvieweradmin'),
        get_string('end_time', 'local_railvieweradmin'),
        get_string('position_x', 'local_railvieweradmin'),
        get_string('position_y', 'local_railvieweradmin'),
        get_string('actions', 'local_railvieweradmin'),
    ];

    foreach ($overlays as $overlay) {
        $editurl = new moodle_url('/local/railvieweradmin/edit_overlay.php', ['id' => $overlay->id]);
        $deleteurl = new moodle_url('/local/railvieweradmin/index_section_overlays.php', ['sectionid' => $sectionid, 'delete' => $overlay->id, 'sesskey' => sesskey()]);

        $actions = html_writer::link($editurl, get_string('edit', 'local_railvieweradmin')) . local_railvieweradmin_delete_link($deleteurl, [
                       'onclick' => "return confirm('" . get_string('confirm_delete', 'local_railvieweradmin') . "');",
                   ]);

        $table->data[] = [
            format_string($overlay->name),
            format_float($overlay->starttime),
            format_float($overlay->endtime),
            format_float($overlay->posx),
            format_float($overlay->posy),
            $actions
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_overlays_for_section', 'local_railvieweradmin'));
}

echo $OUTPUT->footer();

