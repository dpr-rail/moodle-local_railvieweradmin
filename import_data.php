<?php
/**
 * Daten-Import per CSV (einzeln oder als ZIP-Paket): Methoden, Berufserfahrungstypen, Abschnitte, Strecken,
 * Anforderungen (Anzahl Lernfahrten je Strecke/Methode), Ersetzungen, Einsatzstellen, Zuweisungen.
 * Nur neu anlegen; Vorschau vor dem Schreiben; Exporte im Importformat zum Ergänzen.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

$context = context_system::instance();
// Zugang: wer mindestens einen Dateityp importieren darf; je Typ wird das passende Recht einzeln geprüft.
if (!has_any_capability(['local/railvieweradmin:editsections', 'local/railvieweradmin:manageeinsatzstellen', 'local/railvieweradmin:assignroutes'], $context)) {
    throw new required_capability_exception($context, 'local/railvieweradmin:editsections', 'nopermissions', '');
}

use local_railvieweradmin\data_import;
use local_railvieweradmin\data_export;

$self = new moodle_url('/local/railvieweradmin/import_data.php');

// ---- Vorlagen/Exporte (Download)
if ($exporttype = optional_param('export', '', PARAM_ALPHANUMEXT)) {
    require_sesskey();
    $mode = optional_param('mode', 'missing', PARAM_ALPHA);
    if (!in_array($mode, ['missing', 'existing', 'blank'], true)) {
        $mode = 'missing';
    }
    $build = function(string $type) use ($mode) {
        if ($mode === 'blank') {
            $defs = data_import::definitions()[$type];
            return ['header' => $defs['header'] ?? array_keys($defs['columns']), 'rows' => $defs['sample']];
        }
        return data_export::build($type, $mode);
    };
    if ($exporttype === 'all') {
        $tmp = make_temp_directory('local_railvieweradmin') . '/' . sha1(uniqid('', true)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE);
        foreach (data_import::TYPES as $i => $type) {
            if (!has_capability(data_import::required_capability($type), $context)) {
                continue;
            }
            $zip->addFromString(sprintf('%02d_%s.csv', $i + 1, $type), data_export::to_csv($build($type)));
        }
        $zip->close();
        send_temp_file($tmp, 'railviewer_' . $mode . '_' . date('Ymd') . '.zip');
    }
    if (!in_array($exporttype, data_import::TYPES, true)) {
        throw new moodle_exception('invalidparameter');
    }
    require_capability(data_import::required_capability($exporttype), $context);
    send_file(data_export::to_csv($build($exporttype)), $exporttype . '_' . $mode . '.csv', 0, 0, true, true, 'text/csv');
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url($self);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('import_data_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('import_data_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('import_data_title', 'local_railvieweradmin'));

$tmproot = make_temp_directory('local_railvieweradmin/import');

/** Lädt gespeicherte Dateien eines Tokens und parst sie neu. */
function local_railvieweradmin_import_load(string $tmproot, string $token): ?array {
    global $SESSION;
    if (!preg_match('/^[a-f0-9]{32}$/', $token) || ($SESSION->railviewer_import_token ?? '') !== $token || !is_dir("$tmproot/$token")) {
        return null;
    }
    $meta = json_decode((string) file_get_contents("$tmproot/$token/meta.json"), true);
    $files = [];
    foreach (data_import::allowed_types() as $type) {
        $path = "$tmproot/$token/$type.csv";
        if (is_file($path)) {
            $content = (string) file_get_contents($path);
            $delim = $meta['delimiter'] === 'auto' ? data_import::detect_delimiter($content) : $meta['delimiter'];
            $files[$type] = data_import::parse_csv($content, $delim, $meta['encoding']);
        }
    }
    return $files;
}

function local_railvieweradmin_import_render(array $output, bool $final) {
    global $OUTPUT;
    foreach ($output as $type => $info) {
        echo $OUTPUT->heading(get_string('import_type_' . $type, 'local_railvieweradmin'), 4);
        foreach ($info['warnings'] as $w) {
            echo $OUTPUT->notification(s($w), empty($info['fatal']) ? 'warning' : 'error');
        }
        [$created, $skipped, $errors] = $info['counts'];
        echo html_writer::div(get_string($final ? 'import_counts_done' : 'import_counts_preview', 'local_railvieweradmin',
            (object) ['created' => $created, 'skipped' => $skipped, 'errors' => $errors]), 'mb-2');
        if (empty($info['results'])) {
            continue;
        }
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $table->head = [get_string('import_line', 'local_railvieweradmin'), get_string('status', 'local_railvieweradmin'), get_string('import_message', 'local_railvieweradmin')];
        $badge = ['created' => ['import_status_created', 'badge-success'], 'skipped' => ['import_status_skipped', 'badge-secondary'],
            'error' => ['import_status_error', 'badge-danger']];
        $shown = 0;
        // Fehler zuerst, dann Neuanlagen, dann Übersprungenes.
        $order = ['error' => 0, 'created' => 1, 'skipped' => 2];
        $results = $info['results'];
        usort($results, function($a, $b) use ($order) { return $order[$a['status']] <=> $order[$b['status']] ?: $a['line'] <=> $b['line']; });
        foreach ($results as $r) {
            if (++$shown > 300) {
                $table->data[] = [html_writer::span('…', ''), '', get_string('import_more_rows', 'local_railvieweradmin', count($results) - 300)];
                break;
            }
            $table->data[] = [$r['line'], html_writer::span(get_string($badge[$r['status']][0], 'local_railvieweradmin'), 'badge ' . $badge[$r['status']][1]), s($r['message'])];
        }
        echo html_writer::div(html_writer::table($table), '', ['style' => 'max-height:420px;overflow:auto']);
    }
}

// ---- Schritt 3: Import ausführen
if (optional_param('confirm', 0, PARAM_INT)) {
    require_sesskey();
    $token = required_param('token', PARAM_ALPHANUM);
    $files = local_railvieweradmin_import_load($tmproot, $token);
    echo $OUTPUT->header();
    if ($files === null) {
        echo $OUTPUT->notification(get_string('import_session_expired', 'local_railvieweradmin'), 'error');
    } else {
        $output = data_import::run($files, false);
        local_railvieweradmin_import_render($output, true);
        remove_dir("$tmproot/$token");
        unset($SESSION->railviewer_import_token);
    }
    echo html_writer::link($self, get_string('import_again', 'local_railvieweradmin'), ['class' => 'btn btn-secondary mt-2']);
    echo $OUTPUT->footer();
    exit;
}

$mform = new \local_railvieweradmin\forms\import_data_form($self);
echo $OUTPUT->header();

if (!data_import::supports_dry_run()) {
    echo $OUTPUT->notification(get_string('import_no_dryrun', 'local_railvieweradmin'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// ---- Schritt 2: Datei lesen und Vorschau
if ($data = $mform->get_data()) {
    $token = bin2hex(random_bytes(16));
    $dir = "$tmproot/$token";
    check_dir_exists($dir);
    $filename = $mform->get_new_filename('importfile');
    $tmpfile = "$dir/upload";
    $mform->save_file('importfile', $tmpfile, true);
    $found = 0;
    $notices = [];
    $allowed = data_import::allowed_types();
    $store = function(string $type, string $content) use ($dir, &$found, $allowed, &$notices) {
        if (!in_array($type, $allowed, true)) {
            $notices[] = get_string('import_type_not_allowed', 'local_railvieweradmin', get_string('import_type_' . $type, 'local_railvieweradmin'));
            return;
        }
        file_put_contents("$dir/$type.csv", $content);
        $found++;
    };
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($tmpfile) === true && $zip->numFiles <= 40) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (substr($entry, -1) === '/' || strtolower(pathinfo($entry, PATHINFO_EXTENSION)) !== 'csv') {
                    continue;
                }
                $type = data_import::type_from_filename(basename($entry));
                $stat = $zip->statIndex($i);
                if (!$type || $stat['size'] > 5 * 1024 * 1024) {
                    $notices[] = get_string('import_file_ignored', 'local_railvieweradmin', basename($entry));
                    continue;
                }
                $store($type, $zip->getFromIndex($i));
            }
            $zip->close();
        } else {
            $notices[] = get_string('import_zip_failed', 'local_railvieweradmin');
        }
    } else {
        $type = $data->filetype === 'auto' ? data_import::type_from_filename($filename) : $data->filetype;
        if ($type && in_array($type, data_import::TYPES, true)) {
            $store($type, file_get_contents($tmpfile));
        } else {
            $notices[] = get_string('import_type_unknown', 'local_railvieweradmin', $filename);
        }
    }
    @unlink($tmpfile);
    foreach ($notices as $n) {
        echo $OUTPUT->notification(s($n), 'warning');
    }
    if (!$found) {
        remove_dir($dir);
    } else {
        file_put_contents("$dir/meta.json", json_encode(['delimiter' => $data->delimiter, 'encoding' => $data->encoding]));
        $SESSION->railviewer_import_token = $token;
        $files = local_railvieweradmin_import_load($tmproot, $token);
        $output = data_import::run($files, true);
        echo $OUTPUT->heading(get_string('import_preview', 'local_railvieweradmin'), 3);
        echo html_writer::div(get_string('import_preview_note', 'local_railvieweradmin'), 'alert alert-info');
        local_railvieweradmin_import_render($output, false);
        $totalcreated = array_sum(array_map(function($o) { return $o['counts'][0]; }, $output));
        if ($totalcreated > 0) {
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $self->out(false)]);
            foreach (['sesskey' => sesskey(), 'confirm' => 1, 'token' => $token] as $k => $v) {
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
            }
            echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('import_run', 'local_railvieweradmin')]);
            echo ' ' . html_writer::link($self, get_string('cancel'), ['class' => 'btn btn-secondary']);
            echo html_writer::end_tag('form');
        } else {
            echo $OUTPUT->notification(get_string('import_nothing_new', 'local_railvieweradmin'), 'info');
            echo html_writer::link($self, get_string('import_again', 'local_railvieweradmin'), ['class' => 'btn btn-secondary']);
        }
        echo $OUTPUT->footer();
        exit;
    }
}

// ---- Schritt 1: Beschreibung, Vorlagen/Exporte, Upload
echo html_writer::tag('p', get_string('import_data_desc', 'local_railvieweradmin'));

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [get_string('import_filetype', 'local_railvieweradmin'), get_string('import_columns', 'local_railvieweradmin'),
    get_string('import_downloads', 'local_railvieweradmin')];
foreach (data_import::TYPES as $i => $type) {
    if (!in_array($type, data_import::allowed_types(), true)) {
        continue;
    }
    $defs = data_import::definitions()[$type];
    $cols = [];
    foreach ($defs['columns'] as $col => $required) {
        $cols[] = $required ? html_writer::tag('strong', s($col) . '*') : s($col);
    }
    $links = [];
    foreach (['blank' => 'import_dl_blank', 'missing' => 'import_dl_missing', 'existing' => 'import_dl_existing'] as $mode => $str) {
        $links[] = html_writer::link(new moodle_url($self, ['export' => $type, 'mode' => $mode, 'sesskey' => sesskey()]), get_string($str, 'local_railvieweradmin'));
    }
    $table->data[] = [sprintf('%02d_%s.csv', $i + 1, $type) . html_writer::div(get_string('import_type_' . $type, 'local_railvieweradmin'), 'text-muted small'),
        implode(', ', $cols), implode(' · ', $links)];
}
echo html_writer::table($table);
echo html_writer::tag('p', get_string('import_all_zip', 'local_railvieweradmin') . ': '
    . html_writer::link(new moodle_url($self, ['export' => 'all', 'mode' => 'blank', 'sesskey' => sesskey()]), get_string('import_dl_blank', 'local_railvieweradmin')) . ' · '
    . html_writer::link(new moodle_url($self, ['export' => 'all', 'mode' => 'missing', 'sesskey' => sesskey()]), get_string('import_dl_missing', 'local_railvieweradmin')) . ' · '
    . html_writer::link(new moodle_url($self, ['export' => 'all', 'mode' => 'existing', 'sesskey' => sesskey()]), get_string('import_dl_existing', 'local_railvieweradmin')));
echo html_writer::tag('h3', get_string('import_upload', 'local_railvieweradmin'));
$mform->display();
echo $OUTPUT->footer();
