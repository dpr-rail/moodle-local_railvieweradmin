<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_section', 'local_railvieweradmin') : get_string('create_new_section', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('edit_section', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->navbar->add($id ? get_string('edit_section', 'local_railvieweradmin') : get_string('create_new_section', 'local_railvieweradmin'));

// CSS für Vimeo-Auswahl hinzufügen
$PAGE->requires->css(local_railvieweradmin_asset_url('/styles.css'));

echo $OUTPUT->header();

global $DB;

$edgecount = 0;

if ($id) {
    $section = $DB->get_record('railviewer_sections', ['id' => $id], '*', MUST_EXIST);
    if ($section->location_type === 'edge') {
        $edgecount = $DB->count_records('railviewer_section_edges', ['sectionid' => $id]);
    }
    // chain_json wird nur beim erstmaligen Anlegen von der Karte aus ausgewertet, nicht beim
    // Bearbeiten eines bereits existierenden Abschnitts - sonst würde ein leeres Feld hier die
    // schon gespeicherten railviewer_section_edges-Zeilen versehentlich löschen (siehe unten).
    $section->chain_json = '';
    // Für das generische Anbieter-Auswahlformular (section_form.php) zu einem einzigen
    // "driveId|itemId"-Feld zusammengefasst - siehe classes/sharepoint_api.php für den Grund,
    // warum SharePoint (anders als Vimeo/ignite) zwei IDs zur eindeutigen Identifikation braucht.
    $section->sharepoint_video_id = !empty($section->sharepoint_item_id)
        ? $section->sharepoint_drive_id . '|' . $section->sharepoint_item_id : '';
    $section->s3_object_key = $section->s3_object_key ?? '';
} else {
    $section = new stdClass();
    $section->id = 0;
    $section->name = '';
    $section->description = '';
    $section->videofileitemid = '';
    $section->vimeo_video_id = '';
    $section->ignite_video_id = '';
    $section->sharepoint_drive_id = '';
    $section->sharepoint_item_id = '';
    $section->sharepoint_video_id = '';
    $section->s3_object_key = '';
    // Standardquelle: die erste tatsächlich eingerichtete Quelle (nicht mehr hart "upload" -
    // das kann jetzt ebenfalls deaktiviert sein, siehe enable_upload_source-Setting). Ohne diese
    // Anpassung würde ein neuer Abschnitt trotz deaktiviertem Upload weiterhin "Upload (nicht
    // mehr konfiguriert)" vorausgewählt bekommen, nur weil edit_section.php es hart voreinstellt.
    $configuredsources = local_railvieweradmin_get_configured_video_sources();
    $section->video_source = 'upload';
    foreach ($configuredsources as $sourcekey => $isconfigured) {
        if ($isconfigured) {
            $section->video_source = $sourcekey;
            break;
        }
    }

    // Neuanlage per Kartenklick (streckenmap.php) - siehe Implementierungsplan.
    $section->location_type = optional_param('location_type', '', PARAM_ALPHA);
    $section->node_ril100 = optional_param('node_ril100', '', PARAM_ALPHANUMEXT);
    $section->node_name = optional_param('node_name', '', PARAM_TEXT);
    $section->chain_json = optional_param('chain', '', PARAM_RAW);
    $suggestedname = optional_param('suggestedname', '', PARAM_TEXT);
    if ($suggestedname !== '') {
        $section->name = $suggestedname;
    }
}

// Bestehende Videodatei im Filepicker vorbefüllen: ohne diesen Aufruf zeigt das Feld beim
// Bearbeiten einer Sektion mit Upload-Quelle immer "keine Datei ausgewählt", obwohl längst eine
// gespeichert ist - file_prepare_draft_area() muss VOR dem Rendern des Formulars aufgerufen
// werden (Moodle-Standardmuster für Filepicker/Filemanager in einem moodleform). Bei einem
// erneuten Anzeigen nach einem Validierungsfehler liest file_get_submitted_draft_itemid() den
// bereits übermittelten Draft-Itemid statt hier versehentlich einen neuen zu erzeugen.
$videodraftitemid = file_get_submitted_draft_itemid('videofile');
file_prepare_draft_area(
    $videodraftitemid,
    $context->id,
    'local_railvieweradmin',
    'sectionvideo',
    $section->id ?: null,
    ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
);
$section->videofile = $videodraftitemid;

// Vorschau des aktuell verknüpften Videos - unabhängig von der Quelle, damit beim Bearbeiten
// sofort erkennbar ist, welches Video hinterlegt ist (bisher nur bei den Overlays vorhanden).
if ($id) {
    echo local_railvieweradmin_render_video_preview($section, $context);
}

$mform = new \local_railvieweradmin\forms\section_form(null, ['data' => $section, 'edgecount' => $edgecount]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('index_sections.php'));
} else if ($data = $mform->get_data()) {
    
    $record = new stdClass();
    $record->id = $data->id;
    $record->name = $data->name;
    $record->description = $data->description;
    $record->video_source = $data->video_source;
    $record->vimeo_video_id = isset($data->vimeo_video_id) ? $data->vimeo_video_id : '';
    $record->ignite_video_id = isset($data->ignite_video_id) ? $data->ignite_video_id : '';
    $record->s3_object_key = ($data->video_source === 's3' && !empty($data->s3_object_key)) ? $data->s3_object_key : '';

    // sharepoint_video_id kommt als "driveId|itemId" aus dem generischen Anbieter-Auswahlfeld
    // (siehe section_form.php/ajax/get_sharepoint_videos.php) und wird hier wieder in die beiden
    // eigenen DB-Spalten aufgeteilt.
    $record->sharepoint_drive_id = '';
    $record->sharepoint_item_id = '';
    if (!empty($data->sharepoint_video_id) && strpos($data->sharepoint_video_id, '|') !== false) {
        [$record->sharepoint_drive_id, $record->sharepoint_item_id] = explode('|', $data->sharepoint_video_id, 2);
    }

    $record->location_type = !empty($data->location_type) ? $data->location_type : null;
    $record->node_ril100 = !empty($data->node_ril100) ? $data->node_ril100 : null;
    $record->node_name = !empty($data->node_name) ? $data->node_name : null;
    $record->timemodified = time();

    // Vom Anbieter beim Auswählen gemeldete Dauer übernehmen (siehe section_form.php,
    // selectVideo()) - dient play.php als Fallback, falls der eingebettete Player die Dauer
    // beim Abspielen nicht live ermitteln kann. Nur setzen, wenn tatsächlich ein neuer,
    // brauchbarer Wert da ist - sonst beim Bearbeiten einer bestehenden Sektion OHNE erneute
    // Videoauswahl den schon gespeicherten Wert nicht mit 0 überschreiben (das Feld wird nur
    // befüllt, wenn in DIESER Sitzung tatsächlich ein Video ausgewählt wurde).
    if (in_array($data->video_source, ['vimeo', 'ignite', 'sharepoint'], true) && !empty($data->video_duration)) {
        $record->duration = (int) $data->video_duration;
    }

    
    // Datei-Upload nur wenn "upload" als Quelle gewählt wurde
    if ($data->video_source === 'upload') {
        $fs = get_file_storage();
        $draftitemid = file_get_submitted_draft_itemid('videofile');
        
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'local_railvieweradmin',
            'sectionvideo',
            $record->id,
            ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
        );
    } else {
        // Bei Vimeo-Quelle die Datei-ID löschen
        $record->videofileitemid = '';
    }

    try {
        if ($record->id) {
            $DB->update_record('railviewer_sections', $record);
        } else {
            $record->timecreated = time();
            $record->id = $DB->insert_record('railviewer_sections', $record);
        }

        // Datei speichern nur bei Upload-Quelle
        if ($data->video_source === 'upload') {
            file_save_draft_area_files(
                $draftitemid,
                $context->id,
                'local_railvieweradmin',
                'sectionvideo',
                $record->id,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
            );

            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'local_railvieweradmin', 'sectionvideo', $record->id, 'sortorder', false);
            if (!empty($files)) {
                $file = reset($files);
                $DB->set_field('railviewer_sections', 'videofileitemid', $file->get_id(), ['id' => $record->id]);
            }
        }

        // Kanten-Kette einer per Kartenklick neu angelegten Sektion übernehmen. Nur relevant
        // bei der allerersten Speicherung (chain_json kommt ausschließlich aus dem
        // Neuanlage-Flow von streckenmap.php, siehe section_form.php-Kommentar) - ein
        // bestehender Abschnitt behält seine railviewer_section_edges-Zeilen unverändert.
        if (!empty($data->chain_json)) {
            $chain = json_decode($data->chain_json, true);
            if (is_array($chain)) {
                $DB->delete_records('railviewer_section_edges', ['sectionid' => $record->id]);
                $sortorder = 0;
                foreach ($chain as $link) {
                    if (empty($link['edge_id']) || empty($link['direction'])) {
                        continue;
                    }
                    $edgerecord = (object) [
                        'sectionid' => $record->id,
                        'edge_id' => clean_param($link['edge_id'], PARAM_ALPHANUMEXT),
                        'direction' => clean_param($link['direction'], PARAM_ALPHA),
                        'von_ril100' => clean_param($link['von_ril100'] ?? '', PARAM_ALPHANUMEXT),
                        'bis_ril100' => clean_param($link['bis_ril100'] ?? '', PARAM_ALPHANUMEXT),
                        'streckennummer' => clean_param($link['streckennummer'] ?? '', PARAM_ALPHANUMEXT),
                        'sortorder' => $sortorder++,
                    ];
                    $DB->insert_record('railviewer_section_edges', $edgerecord);
                }
            }
        }

        redirect(new moodle_url('index_sections.php'), get_string('section_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);

    } catch (Exception $e) {
        debugging('edit_section.php: ' . $e->getMessage(), DEBUG_NORMAL);
        echo html_writer::div(get_string('generic_ajax_error', 'local_railvieweradmin'), 'alert alert-danger');
    }
}

$mform->set_data($section);
$mform->display();

echo $OUTPUT->footer();
?>
