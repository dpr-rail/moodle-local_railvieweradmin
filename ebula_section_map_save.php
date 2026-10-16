<?php
require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

header('Content-Type: application/json');

$input = file_get_contents('php://input');
if (empty($input)) {
    echo json_encode(['success' => false, 'message' => get_string('no_input_received', 'local_railvieweradmin')]);
    exit;
}
$data = json_decode($input);
if ($data === null) {
    echo json_encode(['success' => false, 'message' => get_string('json_decode_error', 'local_railvieweradmin')]);
    exit;
}

if (!isset($data->sesskey) || !confirm_sesskey($data->sesskey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => get_string('invalid_session', 'local_railvieweradmin')]);
    exit;
}

// Objekt vorbereiten
$record = new stdClass();
$record->sectionid = (int)$data->sectionid;
$record->videotime = (float)$data->videotime;
$record->line_top = (float)$data->line_top;
$record->is_static = (int)$data->is_static;


global $DB;

try {
    $DB->insert_record('railviewer_ebula_section_map', $record);
    echo json_encode(['success' => true, 'record' => $record]);
} catch (Exception $e) {
    // Interne Fehlerdetails nur ins Moodle-Debug-Log, nicht an den Client.
    debugging('ebula_section_map_save.php: ' . $e->getMessage(), DEBUG_NORMAL);
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}

