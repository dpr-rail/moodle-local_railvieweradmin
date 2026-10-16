<?php
require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$sectionid = optional_param('sectionid', 0, PARAM_INT);

global $DB;

try {
    $points = $DB->get_records('railviewer_ebula_section_map', 
        ['sectionid' => $sectionid], 'videotime DeSC');
    
    $result = [];
    foreach ($points as $point) {
        $result[] = [
            'videotime' => $point->videotime,
            'line_top' => $point->line_top,
            'is_static' => $point->is_static
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'points' => $result]);
} catch (Exception $e) {
    debugging('ebula_section_map_get.php: ' . $e->getMessage(), DEBUG_NORMAL);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
}
