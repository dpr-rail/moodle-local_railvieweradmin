<?php
/**
 * CSV-Import von Abschnittsnamen (optional mit Video-Verweis und Streckenbildung).
 * Spalten: name (Pflicht), description, routename, video_source (vimeo|ignite|sharepoint|s3), video_ref
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$self = new moodle_url('/local/railvieweradmin/import_sections.php');

if (optional_param('sample', 0, PARAM_INT)) {
    $csv = "name;description;routename;video_source;video_ref\n"
        . "Wesel - Emmerich;Rheinquerung;Wesel - Koblenz;vimeo;123456789\n"
        . "Emmerich - Kleve;;Wesel - Koblenz;s3;railviewer/emmerich_kleve.mp4\n"
        . "Kleve - Goch;;;;\n";
    send_file($csv, 'abschnitte_beispiel.csv', 0, 0, true, true, 'text/csv');
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url($self);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('import_sections_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('import_sections_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->navbar->add(get_string('import_sections_title', 'local_railvieweradmin'));

global $DB;

const RV_IMPORT_SOURCES = ['vimeo', 'ignite', 'sharepoint', 's3'];

/**
 * Liest die CSV-Zeilen und prüft sie. Rückgabe: Liste von
 * ['line' => n, 'data' => [...], 'errors' => [...], 'warnings' => [...], 'existingid' => id|null].
 */
function local_railvieweradmin_import_parse(csv_import_reader $reader): array {
    global $DB;
    $columns = array_map(function($c) { return core_text::strtolower(trim($c)); }, $reader->get_columns());
    $rows = [];
    $seen = [];
    $reader->init();
    $line = 1;
    while ($record = $reader->next()) {
        $line++;
        $data = ['name' => '', 'description' => '', 'routename' => '', 'video_source' => '', 'video_ref' => ''];
        foreach ($columns as $i => $col) {
            if (array_key_exists($col, $data)) {
                $data[$col] = trim((string) ($record[$i] ?? ''));
            }
        }
        if (implode('', $data) === '') {
            continue; // Leerzeile
        }
        $errors = [];
        $warnings = [];
        $existingid = null;
        if ($data['name'] === '') {
            $errors[] = get_string('import_err_name', 'local_railvieweradmin');
        } else {
            $key = core_text::strtolower($data['name']);
            if (isset($seen[$key])) {
                $existingid = $seen[$key];
                $warnings[] = get_string('import_warn_duplicate_file', 'local_railvieweradmin');
            } else {
                $found = $DB->get_records_select('railviewer_sections', $DB->sql_equal('name', ':n', false, false), ['n' => $data['name']], 'id', 'id', 0, 1);
                if ($found) {
                    $existingid = (int) key($found);
                    $warnings[] = get_string('import_warn_existing', 'local_railvieweradmin');
                }
            }
            $seen[$key] = $existingid ?? ('line' . $line);
        }
        $source = core_text::strtolower($data['video_source']);
        $ref = $data['video_ref'];
        if ($source !== '' || $ref !== '') {
            if (!in_array($source, RV_IMPORT_SOURCES, true)) {
                $errors[] = get_string('import_err_source', 'local_railvieweradmin');
            } else if ($ref === '') {
                $warnings[] = get_string('import_warn_noref', 'local_railvieweradmin');
            } else {
                $ok = true;
                if ($source === 'vimeo') {
                    $ok = ctype_digit($ref) && strlen($ref) <= 10;
                } else if ($source === 'ignite') {
                    $ok = (bool) preg_match('/^[A-Za-z0-9_-]{1,64}$/', $ref);
                } else if ($source === 'sharepoint') {
                    $parts = explode('|', $ref);
                    $ok = count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '' && strlen($parts[0]) <= 128 && strlen($parts[1]) <= 128;
                } else if ($source === 's3') {
                    $ok = \local_railvieweradmin\s3_client::is_valid_key($ref);
                }
                if (!$ok) {
                    $errors[] = get_string('import_err_ref', 'local_railvieweradmin', $source);
                }
                if ($existingid && $ok) {
                    $warnings[] = get_string('import_warn_video_ignored', 'local_railvieweradmin');
                }
            }
        }
        $data['video_source'] = $source;
        $rows[] = ['line' => $line, 'data' => $data, 'errors' => $errors, 'warnings' => $warnings, 'existingid' => is_int($existingid) ? $existingid : null,
            'dupfile' => is_string($seen[core_text::strtolower($data['name'])] ?? null)];
    }
    return $rows;
}

$iid = optional_param('iid', '', PARAM_INT);
$mform = new \local_railvieweradmin\forms\import_sections_form($self);

// Schritt 3: Import ausführen.
if ($iid && optional_param('confirm', 0, PARAM_INT)) {
    require_sesskey();
    $reader = new csv_import_reader($iid, 'local_railvieweradmin_sections');
    $rows = local_railvieweradmin_import_parse($reader);
    $reader->close();
    $reader->cleanup(true);

    $created = 0;
    $reused = 0;
    $skipped = 0;
    $routescreated = 0;
    $transaction = $DB->start_delegated_transaction();
    $ids = []; // lowercase name => section id
    $routes = []; // routename => [sectionid, ...]
    foreach ($rows as $row) {
        $d = $row['data'];
        if ($row['errors']) {
            $skipped++;
            continue;
        }
        $key = core_text::strtolower($d['name']);
        if (isset($ids[$key])) {
            $sectionid = $ids[$key];
            $reused++;
        } else if ($row['existingid']) {
            $sectionid = $row['existingid'];
            $reused++;
        } else {
            $rec = (object) ['name' => $d['name'], 'description' => $d['description'] !== '' ? $d['description'] : null,
                'duration' => 0, 'video_source' => 'upload', 'timecreated' => time(), 'timemodified' => time()];
            if ($d['video_source'] !== '' && $d['video_ref'] !== '') {
                $rec->video_source = $d['video_source'];
                if ($d['video_source'] === 'vimeo') {
                    $rec->vimeo_video_id = (int) $d['video_ref'];
                } else if ($d['video_source'] === 'ignite') {
                    $rec->ignite_video_id = $d['video_ref'];
                } else if ($d['video_source'] === 'sharepoint') {
                    [$rec->sharepoint_drive_id, $rec->sharepoint_item_id] = explode('|', $d['video_ref']);
                } else if ($d['video_source'] === 's3') {
                    $rec->s3_object_key = $d['video_ref'];
                }
            }
            $sectionid = $DB->insert_record('railviewer_sections', $rec);
            $created++;
        }
        $ids[$key] = (int) $sectionid;
        if ($d['routename'] !== '') {
            $routes[$d['routename']][] = (int) $sectionid;
        }
    }
    foreach ($routes as $routename => $sectionids) {
        $route = $DB->get_record('railviewer_routes', ['name' => $routename], '*', IGNORE_MULTIPLE);
        if (!$route) {
            $route = (object) ['id' => $DB->insert_record('railviewer_routes', (object) ['name' => $routename, 'description' => '']), 'name' => $routename];
            $routescreated++;
        }
        $sort = (int) $DB->get_field_sql('SELECT MAX(sortorder) FROM {railviewer_route_sections} WHERE routeid = ?', [$route->id]);
        $have = $DB->get_fieldset_select('railviewer_route_sections', 'sectionid', 'routeid = ?', [$route->id]);
        foreach ($sectionids as $sid) {
            if (in_array($sid, $have)) {
                continue;
            }
            $DB->insert_record('railviewer_route_sections', (object) ['routeid' => $route->id, 'sectionid' => $sid, 'sortorder' => ++$sort]);
            $have[] = $sid;
        }
    }
    $transaction->allow_commit();
    redirect(new moodle_url('/local/railvieweradmin/index_sections.php'),
        get_string('import_done', 'local_railvieweradmin', (object) compact('created', 'reused', 'skipped', 'routescreated')),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Schritt 1/2: Datei hochladen und Vorschau.
if ($data = $mform->get_data()) {
    $content = $mform->get_file_content('csvfile');
    $iid = csv_import_reader::get_new_iid('local_railvieweradmin_sections');
    $reader = new csv_import_reader($iid, 'local_railvieweradmin_sections');
    $count = $reader->load_csv_content($content, $data->encoding, $data->delimiter_name);
    if ($count === false) {
        echo $OUTPUT->notification(get_string('import_err_file', 'local_railvieweradmin', (string) $reader->get_error()), 'error');
    } else {
        $columns = array_map('core_text::strtolower', array_map('trim', $reader->get_columns()));
        if (!in_array('name', $columns, true)) {
            echo $OUTPUT->notification(get_string('import_err_nocolumn', 'local_railvieweradmin'), 'error');
        } else {
            $rows = local_railvieweradmin_import_parse($reader);
            $okcount = count(array_filter($rows, function($r) { return !$r['errors']; }));
            $table = new html_table();
            $table->head = ['#', get_string('name', 'local_railvieweradmin'), get_string('description', 'local_railvieweradmin'),
                get_string('route', 'local_railvieweradmin'), get_string('video_source', 'local_railvieweradmin'),
                get_string('import_video_ref', 'local_railvieweradmin'), get_string('status', 'local_railvieweradmin')];
            foreach ($rows as $row) {
                $d = $row['data'];
                $msgs = '';
                foreach ($row['errors'] as $e) {
                    $msgs .= html_writer::div(s($e), 'text-danger');
                }
                foreach ($row['warnings'] as $w) {
                    $msgs .= html_writer::div(s($w), 'text-warning');
                }
                if (!$msgs) {
                    $msgs = html_writer::span('OK', 'text-success');
                }
                $table->data[] = [$row['line'], s($d['name']), s($d['description']), s($d['routename']), s($d['video_source']), s($d['video_ref']), $msgs];
            }
            echo html_writer::tag('p', get_string('import_preview_summary', 'local_railvieweradmin', (object) ['ok' => $okcount, 'total' => count($rows)]));
            echo html_writer::div(html_writer::table($table), '', ['style' => 'overflow-x:auto']);
            if ($okcount > 0) {
                echo html_writer::start_tag('form', ['method' => 'post', 'action' => $self->out(false)]);
                foreach (['sesskey' => sesskey(), 'iid' => $iid, 'confirm' => 1] as $k => $v) {
                    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
                }
                echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('import_run', 'local_railvieweradmin')]);
                echo ' ' . html_writer::link($self, get_string('cancel'), ['class' => 'btn btn-secondary']);
                echo html_writer::end_tag('form');
                echo $OUTPUT->footer();
                exit;
            }
        }
    }
    $reader->close();
    $reader->cleanup(true);
}

echo html_writer::tag('p', get_string('import_sections_desc', 'local_railvieweradmin'));
echo html_writer::tag('p', html_writer::link(new moodle_url($self, ['sample' => 1]), get_string('import_sample', 'local_railvieweradmin')));
$mform->display();
echo $OUTPUT->footer();
