<?php
require_once(__DIR__.'/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:deletesections', $context);
require_sesskey();

$id = required_param('id', PARAM_INT);

header('Content-Type: application/json');

try {
    global $DB;

    // Prüfen ob das Overlay existiert
    $overlay = $DB->get_record('railviewer_overlays', ['id' => $id]);
    if (!$overlay) {
        echo json_encode([
            'success' => false,
            'message' => get_string('record_not_found', 'local_railvieweradmin')
        ]);
        exit;
    }

    // Overlay löschen
    $success = $DB->delete_records('railviewer_overlays', ['id' => $id]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => get_string('record_deleted', 'local_railvieweradmin')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => get_string('delete_error', 'local_railvieweradmin')
        ]);
    }

} catch (Exception $e) {
    debugging('ajax/delete_overlay.php: ' . $e->getMessage(), DEBUG_NORMAL);
    echo json_encode([
        'success' => false,
        'message' => get_string('generic_ajax_error', 'local_railvieweradmin')
    ]);
}
