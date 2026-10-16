<?php
require_once(__DIR__.'/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

header('Content-Type: application/json');

global $DB;

$ril100 = required_param('ril100', PARAM_ALPHANUMEXT);

$section = $DB->get_record('railviewer_sections', [
    'location_type' => 'node',
    'node_ril100' => $ril100,
]);

if ($section) {
    echo json_encode([
        'status' => 'exists',
        'sectionid' => (int) $section->id,
        'url' => (new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $section->id]))->out(false),
    ]);
} else {
    echo json_encode(['status' => 'new']);
}
