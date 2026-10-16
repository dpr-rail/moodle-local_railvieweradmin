<?php
require_once(__DIR__.'/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

header('Content-Type: application/json');

global $DB;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['edges']) || !is_array($data['edges'])) {
    echo json_encode(['status' => 'error', 'message' => get_string('no_input_received', 'local_railvieweradmin')]);
    exit;
}

// Kette der angeklickten Kanten normalisieren/absichern (Client liefert
// edge_id + direction je Kettenglied, siehe map-widget.js getCurrentChain()).
$edges = [];
foreach ($data['edges'] as $link) {
    if (!isset($link['edge_id'], $link['direction'])) {
        continue;
    }
    $edges[] = [
        'edge_id' => clean_param($link['edge_id'], PARAM_ALPHANUMEXT),
        'direction' => clean_param($link['direction'], PARAM_ALPHA),
    ];
}

if (empty($edges)) {
    echo json_encode(['status' => 'error', 'message' => get_string('invalid_data', 'local_railvieweradmin')]);
    exit;
}

// Alle bereits vorhandenen Zuordnungen für die angeklickten Kanten holen -
// zeigt, ob (Teile) der Kette schon einem Abschnitt zugeordnet sind.
$matchedsectionids = [];
$matchedcount = 0;
foreach ($edges as $edge) {
    $record = $DB->get_record('railviewer_section_edges', [
        'edge_id' => $edge['edge_id'],
        'direction' => $edge['direction'],
    ]);
    if ($record) {
        $matchedcount++;
        $matchedsectionids[$record->sectionid] = true;
    }
}

if ($matchedcount === 0) {
    echo json_encode(['status' => 'new']);
    exit;
}

if (count($matchedsectionids) === 1) {
    // array_key_first() ist erst ab PHP 7.3 verfügbar - reset()/key() ist die
    // portablere Variante und funktioniert bereits mit den auf Moodle-3.4-
    // Ebene noch unterstützten PHP-Versionen.
    reset($matchedsectionids);
    $sectionid = key($matchedsectionids);
    $existingcount = $DB->count_records('railviewer_section_edges', ['sectionid' => $sectionid]);
    if ($existingcount === $matchedcount && $matchedcount === count($edges)) {
        // Exakt dieselbe Kantenmenge ist bereits einem einzigen Abschnitt zugeordnet.
        echo json_encode([
            'status' => 'exists',
            'sectionid' => (int) $sectionid,
            'url' => (new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $sectionid]))->out(false),
        ]);
        exit;
    }
}

// Teilweise Überschneidung mit einem oder mehreren bestehenden Abschnitten:
// der Admin muss die Auswahl anpassen, bevor ein neuer Abschnitt angelegt
// werden kann (der UNIQUE-Index auf railviewer_section_edges würde das beim
// Speichern ohnehin verhindern - hier nur ein früher, verständlicher Hinweis).
echo json_encode([
    'status' => 'conflict',
    'conflicting_sectionids' => array_map('intval', array_keys($matchedsectionids)),
]);
