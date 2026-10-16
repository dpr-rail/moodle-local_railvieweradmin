<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();
require_once($CFG->dirroot . '/local/railvieweradmin/classes/forms/section_form.php');

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_sections', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('manage_sections', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'));

global $DB, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Löschen
if ($action === 'delete' && $id) {
    require_sesskey();
    require_capability('local/railvieweradmin:deletesections', $context);
    $DB->delete_records('railviewer_sections', ['id' => $id]);
    redirect(new moodle_url('/local/railvieweradmin/index_sections.php'), get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}
else if ($action === 'overlays' && $id) {
    redirect(new moodle_url('/local/railvieweradmin/index_section_overlays.php?sectionid='.$id));
}
else if ($action === 'stops' && $id) {
    redirect(new moodle_url('/local/railvieweradmin/index_section_stops.php?sectionid='.$id));
}

// Anzeige
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sections', 'local_railvieweradmin'));
echo html_writer::link(new moodle_url('/local/railvieweradmin/index.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');
echo html_writer::link(new moodle_url('/local/railvieweradmin/edit_section.php'), get_string('create_new_section', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo ' ' . html_writer::link(new moodle_url('/local/railvieweradmin/import_sections.php'), '📥 ' . get_string('import_sections_title', 'local_railvieweradmin'), ['class' => 'btn btn-secondary']);
if (!empty(get_config('local_railvieweradmin', 'map_enabled'))) {
    echo ' ' . html_writer::link(new moodle_url('/local/railvieweradmin/streckenmap.php'), '🗺️ ' . get_string('streckenmap', 'local_railvieweradmin'), ['class' => 'btn btn-secondary']);
}
echo html_writer::empty_tag('br');

$sections = $DB->get_records('railviewer_sections');

// Nutzung je Abschnitt einsammeln (welche Strecken enthalten ihn, wie viele Zuweisungen hat
// jede dieser Strecken) - ein Löschen hier räumt railviewer_route_sections NICHT automatisch
// mit auf (siehe Warnung in routes.php für genau diesen Fall), daher hier vorab warnen statt
// den Abschnitt kommentarlos unter einer noch genutzten Strecke verschwinden zu lassen.
$usagebysection = [];
if ($sections) {
    $usagers = $DB->get_recordset_sql("
        SELECT rs.id AS linkid, rs.sectionid, r.id AS routeid, r.name AS routename,
               (SELECT COUNT(*) FROM {railviewer_assignments} a WHERE a.routeid = r.id) AS assignmentcount
          FROM {railviewer_route_sections} rs
          JOIN {railviewer_routes} r ON r.id = rs.routeid
    ");
    foreach ($usagers as $u) {
        $usagebysection[$u->sectionid][] = $u;
    }
    $usagers->close();
}

if ($sections) {
    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('name', 'local_railvieweradmin'));
    echo html_writer::tag('th', get_string('duration', 'local_railvieweradmin'));
    echo html_writer::tag('th', get_string('actions', 'local_railvieweradmin'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    
    foreach ($sections as $s) {
        $editurl = new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $s->id]);
        $deleteurl = new moodle_url('/local/railvieweradmin/index_sections.php', ['action' => 'delete', 'id' => $s->id, 'sesskey' => sesskey()]);
        $overlaysurl = new moodle_url('/local/railvieweradmin/index_section_overlays.php', ['sectionid' => $s->id]);
        $ebulaurl = new moodle_url('/local/railvieweradmin/index_ebulas.php', ['action' => 'ebula', 'id' => $s->id]);
        $ebulamapurl = new moodle_url('/local/railvieweradmin/ebula_section_map.php', ['action' => 'ebulasectionmap', 'id' => $s->id]);
        
        $ebulaassigned = false;
        if($DB->get_record('railviewer_ebula_section', ['sectionid' => $s->id], '*')){
            $ebulaassigned = true;
        }
        
        // Optional: Dauer aus Beschreibung extrahieren oder leerlassen
        $dauer = ''; // z.B. parse_duration($s->description);

        // Vor dem Löschen warnen, falls der Abschnitt noch von Strecken (und darüber von
        // Nutzer-Zuweisungen) verwendet wird - ein Löschen würde sonst verwaiste Referenzen
        // hinterlassen (siehe Warnung auf routes.php) und ggf. echten Nutzern Inhalt entziehen.
        $usage = $usagebysection[$s->id] ?? [];
        if (!empty($usage)) {
            $routenames = array_map(function($u) { return format_string($u->routename); }, $usage);
            $totalassignments = array_sum(array_map(function($u) { return (int) $u->assignmentcount; }, $usage));
            $confirmmessage = get_string('confirm_delete_section_used', 'local_railvieweradmin', (object) [
                'routes' => implode(', ', $routenames),
                'assignments' => $totalassignments,
            ]);
        } else {
            $confirmmessage = get_string('confirm_delete', 'local_railvieweradmin');
        }

        $actions = html_writer::link($editurl, get_string('edit', 'local_railvieweradmin')) . local_railvieweradmin_delete_link($deleteurl, [
                       'onclick' => 'return confirm(' . json_encode($confirmmessage) . ');',
                   ]) . ' | ' .
                   html_writer::link($overlaysurl, get_string('edit_overlays', 'local_railvieweradmin')) . ' | ' .
                   html_writer::link($ebulaurl, get_string('select_ebula_schedule', 'local_railvieweradmin'));
        
        if (!empty($ebulaassigned) && $ebulaassigned === true) {
            $actions .= ' | ' . html_writer::link($ebulamapurl, get_string('map_ebula_schedule_to_video', 'local_railvieweradmin'));
        }
        
        $namecell = format_string($s->name);
        if (!empty($s->location_type)) {
            $namecell .= ' ' . html_writer::tag('span', '🗺️', ['title' => get_string('streckenmap_linked', 'local_railvieweradmin')]);
        }
        if (!empty($usage)) {
            $namecell .= ' ' . html_writer::tag('span', '🔗', [
                'title' => get_string('section_used_in_routes', 'local_railvieweradmin', implode(', ', $routenames)),
            ]);
        }

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $namecell);
        echo html_writer::tag('td', $dauer);
        echo html_writer::tag('td', $actions);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo $OUTPUT->footer();
