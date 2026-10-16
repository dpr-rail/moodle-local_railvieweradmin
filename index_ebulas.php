<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);

$sectionid = optional_param('id', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/railvieweradmin/index_ebulas.php', ['id' => $sectionid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('ebula_timetable', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('choose_ebula_timetable', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->navbar->add(get_string('ebula_timetable', 'local_railvieweradmin'));

echo $OUTPUT->header();

global $DB, $CFG;

// Formularverarbeitung

$section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);

// Vorhandene anzeigen
echo html_writer::tag('hr', '');
echo html_writer::tag('h3', get_string('assigned_timetable', 'local_railvieweradmin'));

if (empty($DB->get_record('railviewer_ebula_section', ['sectionid' => $sectionid]))) {
    echo html_writer::div(get_string('no_timetable_assigned_yet', 'local_railvieweradmin'), 'alert alert-warning');
} else {
    $record = $DB->get_record('railviewer_ebula_section', ['sectionid' => $sectionid]);
    echo "<style>
        table {border-collapse: collapse; width: 100%;}
        td, th {border-left: 1px solid #ccc; border-right: 1px solid black; padding: 4px; text-align: left;}
        .simplified {border: 1px solid black; border-collapse: collapse; width: 100%; margin-bottom: 0.5em;}
        .simplified td, .simplified th {border-left: 1px solid black; border-right: 1px solid black; padding: 2px;}
        .simplified td:first-child, .simplified th:first-child {border-left: none;}
        .simplified td:last-child, .simplified th:last-child {border-right: none;}
        .inverse-speed { background-color: black; color: white; }
    </style>";
    
    $deleteurl = new moodle_url('/local/railvieweradmin/ebula_section_delete.php', ['id' => $record->id, 'sesskey' => sesskey()]);
    $confirmdelete = get_string('confirm_delete_this_section', 'local_railvieweradmin');
    
    if (has_capability('local/railvieweradmin:deletesections', $context)) {
        echo html_writer::tag('button', '🗑️ ' . get_string('delete_assigned_timetable', 'local_railvieweradmin'), [
            'type' => 'button',
            'class' => 'btn btn-danger',
            'onclick' => "if (confirm('{$confirmdelete}')) { window.location.href = '{$deleteurl}'; }"
        ]);
    }
    
    echo "<table class='simplified'>";
    echo "<tr><th>" . get_string('km', 'local_railvieweradmin') . "</th><th>" . get_string('speed', 'local_railvieweradmin') . "</th><th>" . get_string('station', 'local_railvieweradmin') . "</th></tr>";
    // Defense in depth: das HTML wurde bereits beim Speichern (ebula_section_save.php) bereinigt,
    // wird hier aber zusätzlich nochmal gefiltert ausgegeben statt roh per echo.
    echo format_text($record->html, FORMAT_HTML, ['noclean' => false, 'context' => $context]);
    echo "</table>";
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', get_string('available_timetables', 'local_railvieweradmin'));

$ebulas = $DB->get_records('railviewer_ebulas', null, 'id DESC');

// Für jeden Roh-Fahrplan die Abschnitte einsammeln, die einen daraus stammenden Ausschnitt
// zugewiesen bekommen haben (railviewer_ebula_section.ebulaid) - vorher war das nirgends
// erkennbar. Zuweisungen von vor Einführung dieses Felds haben ebulaid = NULL und bleiben
// entsprechend ohne erkennbare Zuordnung.
$linkedsectionsbyebula = [];
if (!empty($ebulas)) {
    $links = $DB->get_records_sql("
        SELECT es.id AS linkid, es.ebulaid, s.id AS sectionid, s.name AS sectionname
          FROM {railviewer_ebula_section} es
          JOIN {railviewer_sections} s ON s.id = es.sectionid
         WHERE es.ebulaid IS NOT NULL
    ");
    foreach ($links as $link) {
        $linkedsectionsbyebula[$link->ebulaid][] = $link;
    }
}

if (empty($ebulas)) {
    echo html_writer::div(get_string('no_ebula_timetables_available_yet', 'local_railvieweradmin'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('train_number', 'local_railvieweradmin'),
        get_string('selection_string', 'local_railvieweradmin'),
        get_string('uploaded_at', 'local_railvieweradmin'),
        get_string('linked_sections_col', 'local_railvieweradmin'),
        get_string('actions', 'local_railvieweradmin')
    ];
    $table->data = [];

    foreach ($ebulas as $e) {
        $zugnr = '–';
        $auswahl = '–';

        // XML parsen
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($e->xml);
        libxml_clear_errors();

        if ($xml !== false) {
            $namespaces = $xml->getNamespaces(true);
            if (isset($namespaces[''])) {
                $xml->registerXPathNamespace('ns', $namespaces['']);
                $buchfahrplan = $xml->xpath('//ns:Buchfahrplan');
                if (!empty($buchfahrplan)) {
                    $zugnr = (string) $buchfahrplan[0]['zugNr'];
                    $auswahl = (string) $buchfahrplan[0]['auswahlstring'];
                }
            }
        }

        // created_at ist ein Unix-Timestamp (int) - siehe db/upgrade.php für den Fix einer
        // zuvor produktiv abweichenden DATETIME-Spalte. Ohne userdate() zeigte diese Spalte
        // bisher nur die rohe Zahl statt eines lesbaren Datums.
        $zeitstempel = !empty($e->created_at) ? userdate($e->created_at) : '–';

        // Aktionen-Buttons (ggf. anpassen je nach URL-Handling)
        if (empty($DB->get_record('railviewer_ebula_section', ['sectionid' => $sectionid]))) {
            $actions = implode(' | ', [
                html_writer::link(new moodle_url('/local/railvieweradmin/ebula_show.php', ['id' => $e->id]), get_string('show', 'local_railvieweradmin')),
                html_writer::link(new moodle_url('/local/railvieweradmin/ebula_section_edit.php', ['id' => $e->id, 'sectionid' => $sectionid]), get_string('select', 'local_railvieweradmin')),
            ]);
        } else {
            $actions = html_writer::div(get_string('delete_existing_assignment_first', 'local_railvieweradmin'), 'alert alert-warning');
        }

        // Welche Abschnitte nutzen einen aus DIESEM Roh-Fahrplan stammenden Ausschnitt?
        $linkedsections = $linkedsectionsbyebula[$e->id] ?? [];
        if (empty($linkedsections)) {
            $linkedcell = html_writer::tag('span', get_string('no_section_linked', 'local_railvieweradmin'), ['class' => 'text-muted']);
        } else {
            $linkedcell = implode(', ', array_map(function($l) {
                return html_writer::link(
                    new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $l->sectionid]),
                    format_string($l->sectionname)
                );
            }, $linkedsections));
        }

        $table->data[] = [$zugnr, $auswahl, $zeitstempel, $linkedcell, $actions];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
