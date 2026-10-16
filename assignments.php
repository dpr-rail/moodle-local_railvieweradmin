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
require_once(__DIR__.'/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:assignroutes', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/assignments.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_assignments', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('manage_assignments', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_assignments', 'local_railvieweradmin'));
$PAGE->requires->css(local_railvieweradmin_asset_url('/styles.css'));

echo $OUTPUT->header();

global $DB;

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Löschen
if ($action === 'delete' && $id) {
    require_sesskey();
    $DB->delete_records('railviewer_assignments', ['id' => $id]);
    redirect(new moodle_url('/local/railvieweradmin/assignments.php'), get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Hole alle Zuweisungen mit Route und Userdaten
$sql = "
    SELECT ra.*, u.firstname, u.lastname, r.name AS routename,
           r.pairedrouteid, r.direction,
           (SELECT COUNT(*) FROM {railviewer_views} v
            WHERE v.assignmentid = ra.id AND v.userid = ra.userid AND v.sectionid = -1) AS completed_views
    FROM {railviewer_assignments} ra
    JOIN {user} u ON u.id = ra.userid
    JOIN {railviewer_routes} r ON r.id = ra.routeid
    ORDER BY u.lastname, u.firstname, ra.id
";

$assignments = $DB->get_records_sql($sql);

// Für den "Karte ansehen"-Link je Nutzer: EINE für den Admin selbst erreichbare, generische
// (nicht an eine einzelne Strecke gebundene) mod_railviewer-Aktivität suchen - dieselbe cmid
// funktioniert für jeden Nutzer-Link, da map.php mit ?userid=... die Zuweisungen des
// BETRACHTETEN Nutzers lädt, unabhängig davon, über welche Aktivität man dorthin kam.
global $USER;
$mapcmid = null;
$genericcms = $DB->get_records_sql("
    SELECT cm.id AS cmid, cm.course AS courseid
      FROM {railviewer} r
      JOIN {course_modules} cm ON cm.instance = r.id
      JOIN {modules} m ON m.id = cm.module AND m.name = 'railviewer'
     WHERE (r.routeid IS NULL OR r.routeid = 0)
");
foreach ($genericcms as $candidate) {
    $course = get_course($candidate->courseid);
    if (!can_access_course($course, $USER)) {
        continue;
    }
    $modinfo = get_fast_modinfo($course, $USER->id);
    $cm = $modinfo->get_cm($candidate->cmid);
    if ($cm && $cm->uservisible && has_capability('mod/railviewer:view', $cm->context)) {
        $mapcmid = $cm->id;
        break;
    }
}

// Gruppiere nach User
$grouped = [];
foreach ($assignments as $assign) {
    $userid = $assign->userid;
    if (!isset($grouped[$userid])) {
        $grouped[$userid] = [
            'name' => fullname($assign),
            'assignments' => []
        ];
    }
    $grouped[$userid]['assignments'][] = $assign;
}
echo html_writer::start_div('railviewer-page-actions');
echo html_writer::link(new moodle_url('/local/railvieweradmin/index.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo html_writer::link(new moodle_url('edit_assignment.php'), get_string('add_assignment', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
echo html_writer::end_div();

if (empty($assignments)) {
    // Leerer Zustand: gar keine Zuweisungen vorhanden.
    echo html_writer::div(get_string('no_assignments_yet', 'local_railvieweradmin'), 'alert alert-info');
    echo html_writer::link(new moodle_url('edit_assignment.php'), get_string('add_assignment', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->footer();
    exit;
}

// Kennzahlen für die Übersichtsleiste oben auf der Seite.
$totalusers = count($grouped);
$totalassignments = count($assignments);
$completedcount = 0;
foreach ($assignments as $a) {
    if (\local_railvieweradmin\learning::is_complete($a)) {
        $completedcount++;
    }
}
$opencount = $totalassignments - $completedcount;

echo html_writer::start_div('railviewer-stats');
$stats = [
    ['value' => $totalusers, 'label' => get_string('stat_users', 'local_railvieweradmin'), 'class' => ''],
    ['value' => $totalassignments, 'label' => get_string('stat_assignments', 'local_railvieweradmin'), 'class' => ''],
    ['value' => $completedcount, 'label' => get_string('stat_completed', 'local_railvieweradmin'), 'class' => 'railviewer-stat-success'],
    ['value' => $opencount, 'label' => get_string('stat_open', 'local_railvieweradmin'), 'class' => 'railviewer-stat-danger'],
];
foreach ($stats as $stat) {
    echo html_writer::start_div('railviewer-stat ' . $stat['class']);
    echo html_writer::div($stat['value'], 'railviewer-stat-value');
    echo html_writer::div($stat['label'], 'railviewer-stat-label');
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Suchfeld zum Filtern der Nutzer-Karten - bewusst als eigene Zeile, nicht neben dem
// "Zuweisung hinzufügen"-Button, sonst wirkt es wie ein zusammengehöriges Suche-und-Anlegen-Paar.
echo html_writer::start_div('railviewer-toolbar');
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'id' => 'railviewer-assignment-filter',
    'class' => 'form-control',
    'placeholder' => get_string('search_assignments', 'local_railvieweradmin'),
]);
echo html_writer::end_div();

// Ausgabe: eine Karte je Nutzer, darin eine Tabelle mit dessen Zuweisungen.
echo html_writer::start_div('railviewer-assignment-list');
foreach ($grouped as $userid => $data) {
    $userdone = 0;
    foreach ($data['assignments'] as $a) {
        if (\local_railvieweradmin\learning::is_complete($a)) {
            $userdone++;
        }
    }
    $usertotal = count($data['assignments']);
    // badge-pill/-secondary gibt es in dieser Bootstrap-Version nicht (siehe styles.css);
    // .badge ist ohnehin schon abgerundet, ohne Modifier-Klasse bleibt es neutral-grau.
    $userbadgeclass = ($userdone === $usertotal) ? 'badge-success' : '';

    echo html_writer::start_div('card railviewer-assignment-card', ['data-username' => core_text::strtolower($data['name'])]);

    echo html_writer::start_div('card-header railviewer-card-header');
    echo html_writer::start_div('railviewer-card-header-info');
    echo html_writer::tag('h3', s($data['name']), ['class' => 'railviewer-card-title']);
    echo html_writer::span("$userdone / $usertotal " . get_string('stat_completed', 'local_railvieweradmin'), trim('badge ' . $userbadgeclass));
    echo html_writer::end_div();
    echo html_writer::link(new moodle_url('edit_assignment.php', ['presetuserid' => $userid]), get_string('assign_routes_to_user', 'local_railvieweradmin'), ['class' => 'btn btn-sm btn-outline-primary']);
    if (\local_railvieweradmin\learning::enabled() && has_capability('local/railvieweradmin:viewprogress', $context)) {
        echo html_writer::link(new moodle_url('/local/railvieweradmin/compliance_user.php', ['userid' => $userid]),
            '📋 ' . get_string('compliance_user_link', 'local_railvieweradmin'), ['class' => 'btn btn-sm btn-outline-secondary']);
    }
    if ($mapcmid) {
        echo html_writer::link(
            new moodle_url('/mod/railviewer/map.php', ['id' => $mapcmid, 'userid' => $userid]),
            '🗺️ ' . get_string('view_user_map', 'local_railvieweradmin'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );
    }
    echo html_writer::end_div();

    echo html_writer::start_div('card-body railviewer-card-body');
    echo html_writer::start_tag('table', ['class' => 'table table-sm railviewer-assignment-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('route', 'local_railvieweradmin'));
    echo html_writer::tag('th', get_string('progress_col', 'local_railvieweradmin'));
    echo html_writer::tag('th', get_string('status', 'local_railvieweradmin'), ['class' => 'railviewer-col-center']);
    echo html_writer::tag('th', get_string('actions', 'local_railvieweradmin'), ['class' => 'railviewer-col-right']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    // Rendert eine einzelne Zuweisungs-Zeile; wird sowohl für Einzeleinträge als auch für
    // beide Zeilen eines Hin-/Rückfahrt-Paars verwendet (siehe $rowclass-Parameter).
    $renderassignmentrow = function ($a, $rowclass = '') use ($OUTPUT) {
        $isdone = \local_railvieweradmin\learning::is_complete($a);
        $percent = $a->required_views > 0 ? min(100, (int) round(100 * $a->completed_views / $a->required_views)) : 0;

        // Eigene, einfache Fortschrittsbalken-Markierung statt Bootstraps .progress/.progress-bar:
        // Die in dieser Moodle-Instanz gebundelte Bootstrap-Version ist eine sehr frühe 4.0-Alpha,
        // deren .progress-Komponente auf das native <progress value="…">-Element zielt und für
        // einen klassischen div-basierten Balken keine Füllfarbe liefert - er blieb unsichtbar.
        $progresscell = html_writer::start_div('railviewer-progress-track');
        $progresscell .= html_writer::div('', 'railviewer-progress-bar' . ($isdone ? ' is-done' : ''), [
            'role' => 'progressbar',
            'style' => "width: {$percent}%;",
            'aria-valuenow' => $percent,
            'aria-valuemin' => 0,
            'aria-valuemax' => 100,
        ]);
        $progresscell .= html_writer::end_div();
        $progresscell .= html_writer::div("{$a->completed_views} / {$a->required_views}", 'text-muted railviewer-progress-caption');
        if (\local_railvieweradmin\learning::enabled()) {
            $lstatus = \local_railvieweradmin\learning::status($a);
            if (!empty($a->waived)) {
                $progresscell .= html_writer::div(get_string('waived_label', 'local_railvieweradmin') . ': '
                    . s((string) $a->waivereason), 'text-muted railviewer-progress-caption');
            } else if ($lstatus['missing']) {
                $parts = [];
                foreach (array_slice($lstatus['missing'], 0, 4) as $m) {
                    $parts[] = $m['sectionname'] . ' – ' . $m['count'] . '× ' . $m['methodname'];
                }
                $more = count($lstatus['missing']) > 4 ? ' …' : '';
                $progresscell .= html_writer::div(get_string('still_missing', 'local_railvieweradmin') . ': '
                    . s(implode('; ', $parts)) . $more, 'text-muted railviewer-progress-caption');
            }
        }

        $statuscell = html_writer::span(
            !empty($a->waived) ? get_string('waived_label', 'local_railvieweradmin')
                : ($isdone ? get_string('status_done', 'local_railvieweradmin') : get_string('status_open', 'local_railvieweradmin')),
            'badge ' . ($isdone ? 'badge-success' : 'badge-warning')
        );

        $editurl = new moodle_url('edit_assignment.php', ['id' => $a->id]);
        $deleteurl = new moodle_url('/local/railvieweradmin/assignments.php', ['action' => 'delete', 'id' => $a->id, 'sesskey' => sesskey()]);
        $actions = html_writer::start_div('railviewer-actions');
        $actions .= html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit', 'local_railvieweradmin')), [
            'class' => 'btn btn-sm btn-outline-secondary',
            'title' => get_string('edit', 'local_railvieweradmin'),
        ]);
        $actions .= html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete', 'local_railvieweradmin')), [
            'class' => 'btn btn-sm btn-outline-danger',
            'title' => get_string('delete', 'local_railvieweradmin'),
            'onclick' => "return confirm('" . get_string('confirm_delete', 'local_railvieweradmin') . "');",
        ]);
        $actions .= html_writer::end_div();

        $routelabel = format_string($a->routename);
        if (!empty($a->pairedrouteid) && !empty($a->direction)) {
            $routelabel = get_string('direction_' . $a->direction, 'local_railvieweradmin') . ': ' . $routelabel;
        }

        echo html_writer::start_tag('tr', ['class' => trim($rowclass)]);
        echo html_writer::tag('td', $routelabel);
        echo html_writer::tag('td', $progresscell);
        echo html_writer::tag('td', $statuscell, ['class' => 'railviewer-col-center']);
        echo html_writer::tag('td', $actions, ['class' => 'railviewer-col-right']);
        echo html_writer::end_tag('tr');
    };

    $groups = local_railvieweradmin_group_by_route_pairing($data['assignments']);
    foreach ($groups as $group) {
        if ($group['type'] === 'single') {
            $renderassignmentrow($group['item']);
            continue;
        }

        [$hin, $rueck] = $group['items'];
        $renderassignmentrow($hin, 'railviewer-assignment-row-paired');
        $renderassignmentrow($rueck, 'railviewer-assignment-row-paired');

        $combined = (object) [
            'completed' => $hin->completed_views + $rueck->completed_views,
            'required' => $hin->required_views + $rueck->required_views,
        ];
        echo html_writer::start_tag('tr', ['class' => 'railviewer-assignment-row-connector']);
        echo html_writer::tag('td', get_string('round_trip_progress', 'local_railvieweradmin', $combined), ['colspan' => 4]);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
}
echo html_writer::end_div(); // railviewer-assignment-list

// Reine Client-Filterung der Karten nach Nutzername, kein Ajax nötig.
$PAGE->requires->js_init_code("
(function() {
    var input = document.getElementById('railviewer-assignment-filter');
    if (!input) { return; }
    input.addEventListener('input', function() {
        var term = input.value.trim().toLowerCase();
        document.querySelectorAll('.railviewer-assignment-card').forEach(function(card) {
            var name = card.getAttribute('data-username') || '';
            card.style.display = name.indexOf(term) !== -1 ? '' : 'none';
        });
    });
})();
");

echo $OUTPUT->footer();
