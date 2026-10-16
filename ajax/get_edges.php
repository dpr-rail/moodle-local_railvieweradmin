<?php
require_once(__DIR__.'/../../../config.php');
require_login();

// Reine Referenzgeodaten (öffentliches Schienennetz-Kantenmodell, keine
// personenbezogenen Daten) - Zugriff daher an "eingeloggt" statt an eine
// bestimmte Capability gebunden, analog zu den übrigen ajax/*.php-Endpunkten
// dieses Plugins.
$file = __DIR__ . '/../geodata/edges.geojson';
if (!is_readable($file)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: private, max-age=3600');
readfile($file);
