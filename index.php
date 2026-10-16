<?php
require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_login();

$context = context_system::instance();
// Das Dashboard steht jedem offen, der mindestens eines der Railviewer-Rechte hat; welche
// Kacheln er sieht, richtet sich unten nach seinen Einzelrechten.
if (!has_any_capability([
    'local/railvieweradmin:editsections',
    'local/railvieweradmin:deletesections',
    'local/railvieweradmin:manageeinsatzstellen',
    'local/railvieweradmin:assignroutes',
    'local/railvieweradmin:viewprogress',
    'local/railvieweradmin:managesettings',
], $context)) {
    throw new required_capability_exception($context, 'local/railvieweradmin:editsections', 'nopermissions', '');
}


$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('adminpagetitle', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('adminpagetitle', 'local_railvieweradmin'));
$PAGE->requires->css(local_railvieweradmin_asset_url('/styles.css'));

echo $OUTPUT->header();

echo html_writer::tag('p', get_string('adminpagedescription', 'local_railvieweradmin'), ['class' => 'railviewer-admin-lead']);

// Kachel-Übersicht statt Pipe-getrennter Textlinks: eine Kachel je Verwaltungsbereich,
// mit kurzer Beschreibung, damit auch neue Admins auf Anhieb sehen, was sich wo befindet.
$tiles = [
    [
        'icon' => '🎬',
        'title' => get_string('manage_sections', 'local_railvieweradmin'),
        'desc' => get_string('tile_sections_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('index_sections.php'),
        'cap' => 'local/railvieweradmin:editsections',
    ],
    [
        'icon' => '🛤️',
        'title' => get_string('assemble_routes', 'local_railvieweradmin'),
        'desc' => get_string('tile_routes_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('routes.php'),
        'cap' => 'local/railvieweradmin:editsections',
    ],
    [
        'icon' => '🏷️',
        'title' => get_string('manage_overlays_nav', 'local_railvieweradmin'),
        'desc' => get_string('tile_overlays_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('index_overlays.php'),
        'cap' => 'local/railvieweradmin:editsections',
    ],
    [
        'icon' => '👥',
        'title' => get_string('assign_routes_to_users', 'local_railvieweradmin'),
        'desc' => get_string('tile_assignments_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('assignments.php'),
        'cap' => 'local/railvieweradmin:assignroutes',
    ],
    [
        'icon' => '📍',
        'title' => get_string('manage_einsatzstellen', 'local_railvieweradmin'),
        'desc' => get_string('tile_einsatzstellen_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('einsatzstellen.php'),
        'cap' => 'local/railvieweradmin:manageeinsatzstellen',
    ],
    [
        'icon' => '🚆',
        'title' => get_string('retrieve_ebula_timetables', 'local_railvieweradmin'),
        'desc' => get_string('tile_ebula_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('ebulas.php'),
        'cap' => 'local/railvieweradmin:editsections',
    ],
];

$tiles[] = ['icon' => '📥', 'title' => get_string('import_data_title', 'local_railvieweradmin'),
    'desc' => get_string('tile_import_desc', 'local_railvieweradmin'),
    'url' => new moodle_url('import_data.php'), 'cap' => 'local/railvieweradmin:editsections'];

// Lernmethoden-Feature: zusätzliche Kacheln.
if (\local_railvieweradmin\learning::enabled()) {
    $tiles[] = ['icon' => '🧭', 'title' => get_string('learning_methods', 'local_railvieweradmin'),
        'desc' => get_string('tile_methods_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('methods.php'), 'cap' => 'local/railvieweradmin:editsections'];
    $tiles[] = ['icon' => '🎓', 'title' => get_string('experience_types', 'local_railvieweradmin'),
        'desc' => get_string('tile_types_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('experience_types.php'), 'cap' => 'local/railvieweradmin:editsections'];
    $tiles[] = ['icon' => '📑', 'title' => get_string('compliance_requirements_title', 'local_railvieweradmin'),
        'desc' => get_string('tile_compliance_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('compliance.php'), 'cap' => 'local/railvieweradmin:viewprogress'];
    $tiles[] = ['icon' => '✅', 'title' => get_string('compliance_user_title', 'local_railvieweradmin'),
        'desc' => get_string('tile_compliance_user_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('compliance_user.php'), 'cap' => 'local/railvieweradmin:viewprogress'];
}

// Kartenkachel nur anzeigen, wenn die Kartenfunktion in den Settings aktiviert ist
// (Feature-Schalter, siehe Implementierungsplan).
if (!empty(get_config('local_railvieweradmin', 'map_enabled'))) {
    $tiles[] = [
        'icon' => '🗺️',
        'title' => get_string('streckenmap', 'local_railvieweradmin'),
        'desc' => get_string('tile_streckenmap_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('streckenmap.php'),
        'cap' => 'local/railvieweradmin:editsections',
    ];
}

// Einstellungen-Kachel nur mit local/railvieweradmin:managesettings - die eigentliche
// Einstellungsseite verlangt dieselbe Capability (siehe settings.php).
// Ohne diese Kachel war die Konfiguration von hier aus überhaupt nicht erreichbar - sie hängt
// zwar unter Website-Administration -> Plugins -> Lokale Plugins, aber das ist von diesem
// Dashboard aus nicht ersichtlich.
if (has_capability('local/railvieweradmin:managesettings', $context)) {
    $tiles[] = [
        'icon' => '⚙️',
        'title' => get_string('settings_tile_title', 'local_railvieweradmin'),
        'desc' => get_string('tile_settings_desc', 'local_railvieweradmin'),
        'url' => new moodle_url('/admin/settings.php', ['section' => 'local_railvieweradmin']),
    ];
}

// Kacheln thematisch gruppieren (Reihenfolge der Gruppen = Reihenfolge der Anzeige).
$groupof = [
    'index_sections.php' => 'content', 'routes.php' => 'content', 'index_overlays.php' => 'content',
    'ebulas.php' => 'content', 'import_data.php' => 'content', 'import_sections.php' => 'content', 'streckenmap.php' => 'content',
    'einsatzstellen.php' => 'assign', 'assignments.php' => 'assign',
    'methods.php' => 'config', 'experience_types.php' => 'config',
    'compliance.php' => 'reports', 'compliance_user.php' => 'reports',
    'settings.php' => 'config',
];
$groups = ['content' => [], 'assign' => [], 'reports' => [], 'config' => []];
foreach ($tiles as $tile) {
    if (!empty($tile['cap']) && !has_capability($tile['cap'], $context)) {
        continue;
    }
    $base = basename($tile['url']->get_path());
    $groups[$groupof[$base] ?? 'content'][] = $tile;
}
// In der Gruppe Einstellungen steht die Plugin-Einstellungsseite vorn, danach die Stammdaten.
if (count($groups['config']) > 1) {
    $last = array_pop($groups['config']);
    array_unshift($groups['config'], $last);
}

foreach ($groups as $key => $grouptiles) {
    if (!$grouptiles) {
        continue;
    }
    echo html_writer::tag('h2', get_string('dashboard_group_' . $key, 'local_railvieweradmin'),
        ['class' => 'railviewer-admin-group-title', 'style' => 'font-size:1.15rem;margin:1.6rem 0 .6rem;padding-bottom:.3rem;border-bottom:1px solid #dee2e6']);
    echo html_writer::start_div('railviewer-admin-grid');
    foreach ($grouptiles as $tile) {
        echo html_writer::start_tag('a', ['class' => 'railviewer-admin-tile', 'href' => $tile['url']->out(false)]);
        echo html_writer::div($tile['icon'], 'railviewer-admin-tile-icon');
        echo html_writer::tag('h3', $tile['title'], ['class' => 'railviewer-admin-tile-title']);
        echo html_writer::div($tile['desc'], 'railviewer-admin-tile-desc');
        echo html_writer::end_tag('a');
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
