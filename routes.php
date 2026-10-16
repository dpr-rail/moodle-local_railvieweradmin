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
require_capability('local/railvieweradmin:editsections', $context);

global $DB;

$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid) {
    require_sesskey();
    require_capability('local/railvieweradmin:deletesections', $context);
    $transaction = $DB->start_delegated_transaction();
    // Erst die Paarung lösen (falls vorhanden) - der Partner wird dadurch zu einer normalen,
    // unverknüpften Strecke statt versehentlich mitgelöscht zu werden.
    local_railvieweradmin_update_route_pairing($deleteid, 0, null);
    $DB->delete_records('railviewer_route_sections', ['routeid' => $deleteid]);
    $DB->delete_records('railviewer_routes', ['id' => $deleteid]);
    $transaction->allow_commit();
    redirect(new moodle_url('/local/railvieweradmin/routes.php'),
        get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$unlinkid = optional_param('unlink', 0, PARAM_INT);
if ($unlinkid) {
    require_sesskey();
    local_railvieweradmin_update_route_pairing($unlinkid, 0, null);
    redirect(new moodle_url('/local/railvieweradmin/routes.php'),
        get_string('route_unlinked', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/routes.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_routes_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('manage_routes_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_routes_title', 'local_railvieweradmin'));

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/railvieweradmin/index.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo html_writer::empty_tag('hr');

echo html_writer::link(new moodle_url('edit_route.php'), get_string('create_new_route', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);

// Suchfeld zum Filtern der Streckenliste nach Name und/oder enthaltenen Abschnitten - gleiches
// Muster (reine Client-Filterung, kein Ajax) wie schon in assignments.php.
echo html_writer::start_div('railviewer-toolbar');
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'id' => 'railviewer-route-filter',
    'class' => 'form-control',
    'placeholder' => get_string('search_routes', 'local_railvieweradmin'),
]);
echo html_writer::end_div();

global $DB;
$routes = $DB->get_records('railviewer_routes');

$table = new html_table();
$table->id = 'railviewer-routes-table';
$table->attributes['class'] = 'generaltable';
$table->head = [
    'ID',
    get_string('name', 'local_railvieweradmin'),
    get_string('description', 'local_railvieweradmin'),
    get_string('route_sections_col', 'local_railvieweradmin'),
    get_string('route_pairing_col', 'local_railvieweradmin'),
    get_string('actions', 'local_railvieweradmin'),
];

foreach ($routes as $route) {
    // Abschnitte als Liste holen - LEFT JOIN statt INNER JOIN: railviewer_sections.sectionid_fk
    // hat zwar ON DELETE CASCADE in install.xml, Moodles XMLDB "foreign"-Keys werden aber auf
    // MySQL/MariaDB nicht als echte DB-Constraints angelegt (nur dokumentarisch/Index) - ein
    // direkt gelöschter Abschnitt kann daher als "verwaiste" Zeile in railviewer_route_sections
    // zurückbleiben. Mit INNER JOIN verschwand so ein Abschnitt einfach kommentarlos aus der
    // Liste; mit LEFT JOIN wird er als fehlend erkannt und als Warnung angezeigt.
    $sectionlinks = $DB->get_records_sql(
        "SELECT rs.id AS linkid, s.name AS sectionname
           FROM {railviewer_route_sections} rs
           LEFT JOIN {railviewer_sections} s ON s.id = rs.sectionid
          WHERE rs.routeid = ?
       ORDER BY rs.sortorder",
        [$route->id]
    );
    $sectionnames = [];
    $missingcount = 0;
    foreach ($sectionlinks as $link) {
        if ($link->sectionname === null) {
            $missingcount++;
        } else {
            $sectionnames[] = format_string($link->sectionname);
        }
    }
    $sectionnames = implode(', ', $sectionnames);
    if ($missingcount > 0) {
        $sectionnames .= html_writer::tag('span',
            ' ⚠️ ' . get_string('missing_sections_warning', 'local_railvieweradmin', $missingcount),
            ['class' => 'text-danger', 'title' => get_string('missing_sections_warning_title', 'local_railvieweradmin')]
        );
    }

    $editurl = new moodle_url('edit_route.php', ['id' => $route->id]);
    $deleteurl = new moodle_url('routes.php', ['delete' => $route->id, 'sesskey' => sesskey()]);
    $reqlink = \local_railvieweradmin\learning::enabled()
        ? ' | ' . html_writer::link(new moodle_url('/local/railvieweradmin/route_requirements.php', ['routeid' => $route->id]),
            get_string('route_requirements', 'local_railvieweradmin'))
        : '';
    $actions = html_writer::link($editurl, get_string('edit', 'local_railvieweradmin')) . $reqlink . local_railvieweradmin_delete_link($deleteurl, [
                   'onclick' => "return confirm('" . get_string('confirm_delete', 'local_railvieweradmin') . "');",
               ]);

    $pairing = '–';
    if (!empty($route->pairedrouteid)) {
        $partnername = $DB->get_field('railviewer_routes', 'name', ['id' => $route->pairedrouteid]);
        if ($partnername !== false) {
            $pairing = get_string('route_pairing_display', 'local_railvieweradmin', (object) [
                'direction' => get_string('direction_' . $route->direction, 'local_railvieweradmin'),
                'partnername' => html_writer::link(
                    new moodle_url('edit_route.php', ['id' => $route->pairedrouteid]),
                    format_string($partnername)
                ),
            ]);
            $unlinkurl = new moodle_url('routes.php', ['unlink' => $route->id, 'sesskey' => sesskey()]);
            $actions .= ' | ' . html_writer::link($unlinkurl, get_string('unlink_route', 'local_railvieweradmin'));
        }
    }

    // Suchbegriff aus Name + tatsächlich vorhandenen Abschnittsnamen (nicht der HTML-formatierte
    // $sectionnames-String mit Warn-Markup) für die Client-Filterung unten.
    $searchtext = mb_strtolower($route->name . ' ' . implode(' ', array_map(function($l) {
        return $l->sectionname ?? '';
    }, $sectionlinks)));

    $row = new html_table_row([$route->id, format_string($route->name), format_text($route->description), $sectionnames, $pairing, $actions]);
    $row->attributes['data-search'] = $searchtext;
    $table->data[] = $row;
}

echo html_writer::table($table);
echo html_writer::div(get_string('search_routes_empty', 'local_railvieweradmin'), 'text-muted', ['id' => 'railviewer-route-filter-empty', 'style' => 'display:none;']);

$PAGE->requires->js_init_code("
(function() {
    var input = document.getElementById('railviewer-route-filter');
    var emptyMsg = document.getElementById('railviewer-route-filter-empty');
    if (!input) { return; }
    var rows = document.querySelectorAll('#railviewer-routes-table tbody tr');
    input.addEventListener('input', function() {
        var term = input.value.trim().toLowerCase();
        var visiblecount = 0;
        rows.forEach(function(row) {
            var haystack = row.getAttribute('data-search') || '';
            var match = haystack.indexOf(term) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) { visiblecount++; }
        });
        if (emptyMsg) { emptyMsg.style.display = (term && visiblecount === 0) ? '' : 'none'; }
    });
})();
");

echo $OUTPUT->footer();

