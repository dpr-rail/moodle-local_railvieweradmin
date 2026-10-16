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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/ebulas.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('ebula_timetables', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('ebula_timetables', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('ebula_timetables', 'local_railvieweradmin'));

global $DB, $CFG;

// Formularverarbeitung
$trainnumber = optional_param('trainnumber', '', PARAM_INT);
$date = optional_param('date', '', PARAM_RAW); // ISO: YYYY-MM-DD
$downloaded = false;
$errors = [];

if (optional_param('submit', '', PARAM_TEXT)) {
    // POST + sesskey: als GET-Formular löste jedes Neuladen (F5) der Ergebnisseite den Abruf
    // erneut aus und legte den Fahrplan nochmals an.
    require_sesskey();
    if (empty($trainnumber)) {
        $errors[] = get_string('train_number_cannot_be_empty', 'local_railvieweradmin');
    } elseif (strlen((string)$trainnumber) > 5) {
        $errors[] = get_string('train_number_max_5_digits', 'local_railvieweradmin');
    }

    if (empty($date)) {
        //$errors[] = get_string('date_cannot_be_empty', 'local_railvieweradmin');
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = get_string('date_format_yyyy_mm_dd', 'local_railvieweradmin');
    }

    if (empty($errors)) {
        // Authentifizierungsdaten
        $apiurl = get_config('local_railvieweradmin', 'ebula_api_url');
        $apiuser = get_config('local_railvieweradmin', 'ebula_api_user');
        $apipass = get_config('local_railvieweradmin', 'ebula_api_password');
        $apikey = get_config('local_railvieweradmin', 'ebula_api_key');
        
        $params=[];
        // Query-Parameter aufbauen
        if (empty($date)) {
            $params = [
                'train-number' => $trainnumber,
            ];
        } else {
            $params = [
                'train-number' => $trainnumber,
                'date' => $date,
            ];
        }
        
        // Dritter Parameter '&' erzwungen: Moodle setzt in lib/setup.php bewusst
        // ini_set('arg_separator.output', '&amp;') (historische XHTML-Absicherung), worauf sich
        // http_build_query() standardmäßig stützt - für eine echte HTTP-Anfrage ungültig. Ohne
        // diesen Fix sah die API bei gleichzeitig gesetzter Zugnummer UND Datum nur einen
        // einzigen, kaputten "train-number"-Parameter statt zwei getrennter Filter.
        $query = http_build_query($params, '', '&');
        $fullurl = $apiurl . '?' . $query;

        // Header + Auth
        $headers = [
            'Accept: application/zip',
            'apikey: ' . $apikey,  // API Key im Header
        ];

        // cURL mit Basic Auth + API Key
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $fullurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERPWD => "$apiuser:$apipass", // Basic Auth
            CURLOPT_TIMEOUT => 20,
        ]);
        $zipdata = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($httpcode === 200 && $zipdata) {
            $tmpzip = tempnam(sys_get_temp_dir(), 'ebula_') . '.zip';
            file_put_contents($tmpzip, $zipdata);
            $zip = new ZipArchive();
            if ($zip->open($tmpzip) === true) {
                ini_set('memory_limit', '512M');
                libxml_use_internal_errors(true);

                // Die EBuLa-API liefert denselben Buchfahrplan im ZIP in mehreren Schema-Versionen
                // (z.B. buchfahrplan/schemes/1.2 UND 1.3) als getrennte XML-Dateien. Das waren die
                // "doppelt gespeicherten" Fahrpläne. Pro Fahrplan (Zugnummer + Auswahlstring +
                // Fahrplanart) wird deshalb nur die Datei mit der höchsten Schema-Version behalten.
                $candidates = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    // Verzeichniseinträge und Nicht-XML-Dateien im ZIP überspringen
                    if (substr($filename, -1) === '/' || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'xml') {
                        continue;
                    }
                    $content = $zip->getFromIndex($i);
                    $xmlobj = simplexml_load_string($content);

                    $version = '0';
                    if ($xmlobj !== false) {
                        $xmlcontent = $xmlobj->asXML();
                        $ns = $xmlobj->getNamespaces(true)[''] ?? '';
                        if (preg_match('#/schemes/([\d.]+)$#', $ns, $m)) {
                            $version = $m[1];
                        }
                        $xmlobj->registerXPathNamespace('ns', $ns);
                        $bfp = $xmlobj->xpath('//ns:Buchfahrplan');
                        $groupkey = $bfp ? ($bfp[0]['zugNr'] . '|' . $bfp[0]['auswahlstring'] . '|' . $bfp[0]['fahrplanart'])
                            : 'file:' . $filename;
                    } else {
                        $xmlcontent = json_encode([
                            'error' => 'Invalid XML',
                            'filename' => $filename,
                            'libxml_errors' => libxml_get_errors()
                        ]);
                        libxml_clear_errors();
                        $groupkey = 'file:' . $filename;
                    }
                    unset($xmlobj, $content);

                    if (!isset($candidates[$groupkey]) || version_compare($version, $candidates[$groupkey]['version'], '>')) {
                        $candidates[$groupkey] = ['version' => $version, 'xml' => $xmlcontent];
                    }
                }

                foreach ($candidates as $candidate) {
                    // Schon identisch gespeicherten Fahrplan (früherer Abruf) nicht erneut anlegen.
                    if ($DB->record_exists_select('railviewer_ebulas', $DB->sql_compare_text('xml') . ' = ' .
                            $DB->sql_compare_text('?', 32768), [$candidate['xml']])) {
                        continue;
                    }
                    $record = new stdClass();
                    $record->xml = $candidate['xml'];
                    $record->created_at = time();
                    $DB->insert_record('railviewer_ebulas', $record);
                }
                $zip->close();
                unlink($tmpzip);
                // Post/Redirect/Get: verhindert erneutes Absenden beim Neuladen der Seite.
                redirect($PAGE->url, get_string('ebula_data_imported_successfully', 'local_railvieweradmin'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $errors[] = get_string('zip_could_not_be_extracted', 'local_railvieweradmin');
            }
        } else {
            $message = get_string('error_retrieving_data_http_code', 'local_railvieweradmin') . " $httpcode – $error";

            // Bei einer Fehlerantwort (4xx/5xx) steckt in $zipdata der Antwort-Body der API
            // (z.B. eine konkrete Fehlermeldung wie "API-Key expired") statt der ZIP-Daten.
            // Den bislang verworfenen Body gekürzt und escaped mit anzeigen, damit man nicht
            // nur den nackten HTTP-Code sieht.
            if (!empty($zipdata)) {
                $bodytext = trim(strip_tags($zipdata));
                if ($bodytext !== '') {
                    if (function_exists('mb_strimwidth')) {
                        $bodytext = mb_strimwidth($bodytext, 0, 500, '…');
                    } else {
                        $bodytext = strlen($bodytext) > 500 ? substr($bodytext, 0, 500) . '…' : $bodytext;
                    }
                    $message .= html_writer::empty_tag('br')
                        . html_writer::tag('small', get_string('error_retrieving_data_response', 'local_railvieweradmin')
                            . ' ' . s($bodytext));
                }
            }

            $errors[] = $message;
        }
    }
}

// Formular
// Header erst nach der Verarbeitung ausgeben, damit redirect() sauber weiterleiten kann.
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('retrieve_ebula_timetables', 'local_railvieweradmin'));

if (!empty($errors)) {
    echo html_writer::div(implode('<br>', $errors), 'alert alert-danger');
}

if ($downloaded) {
    echo html_writer::div(get_string('ebula_data_imported_successfully', 'local_railvieweradmin'), 'alert alert-success');
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::label(get_string('train_number_max_5_digits_label', 'local_railvieweradmin'), 'trainnumber');
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'trainnumber',
    'id' => 'trainnumber',
    'value' => $trainnumber,
    'class' => 'form-control',
    'maxlength' => 5,
    'required' => 'required',
]);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::label(get_string('date_yyyy_mm_dd', 'local_railvieweradmin'), 'date');
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'date',
    'id' => 'date',
    'value' => $date,
    'class' => 'form-control',
]);
echo html_writer::end_tag('div');

echo html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'submit', 'value' => get_string('retrieve', 'local_railvieweradmin'), 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

// Vorhandene anzeigen
echo html_writer::tag('hr', '');
echo html_writer::tag('h3', get_string('saved_entries', 'local_railvieweradmin'));

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
    echo html_writer::div(get_string('no_ebula_data_saved_yet', 'local_railvieweradmin'), 'alert alert-info');
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

        // Aktionen-Buttons. "Bearbeiten"/"Löschen" verlinkten bisher auf ebula_edit.php/
        // ebula_delete.php, die es als Dateien gar nicht gab (toter Link) - jetzt angelegt.
        $deleteurl = new moodle_url('/local/railvieweradmin/ebula_delete.php', ['id' => $e->id, 'sesskey' => sesskey()]);
        $actions = implode(' | ', array_filter([
            html_writer::link(new moodle_url('/local/railvieweradmin/ebula_show.php', ['id' => $e->id]), get_string('show', 'local_railvieweradmin')),
            html_writer::link(new moodle_url('/local/railvieweradmin/ebula_edit.php', ['id' => $e->id]), get_string('edit', 'local_railvieweradmin')),
            local_railvieweradmin_delete_link($deleteurl, [
                'onclick' => 'return confirm(' . json_encode(get_string('confirm_delete', 'local_railvieweradmin')) . ');',
            ], ''),
        ]));

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
