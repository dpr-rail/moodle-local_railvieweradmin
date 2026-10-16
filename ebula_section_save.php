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

require_once(__DIR__.'/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || !isset($data['sesskey']) || !confirm_sesskey($data['sesskey'])) {
    http_response_code(403);
    echo json_encode(['error' => get_string('invalid_session', 'local_railvieweradmin')]);
    exit;
}

if (!isset($data['sectionid'], $data['html'], $data['json'])) {
    http_response_code(400);
    echo json_encode(['error' => get_string('invalid_data', 'local_railvieweradmin')]);
    exit;
}

// Das vom Client gesendete HTML besteht aus markierten Tabellenzeilen (outerHTML) des
// Ebula-Fahrplans. Es wird serverseitig bereinigt, bevor es dauerhaft gespeichert wird
// (index_ebulas.php gibt es später ungeprüft im Adminbereich anderer Nutzer aus).
$record = new stdClass();
$record->sectionid = (int)$data['sectionid'];
// Referenz auf den Roh-Fahrplan, aus dem dieser Ausschnitt stammt - ermöglicht in
// ebulas.php/index_ebulas.php anzuzeigen, welcher Abschnitt zu welchem hochgeladenen Fahrplan
// gehört. Optional/nullable, damit ältere Aufrufe ohne dieses Feld nicht scheitern.
$record->ebulaid = !empty($data['ebulaid']) ? (int)$data['ebulaid'] : null;
$record->html = clean_param($data['html'], PARAM_CLEANHTML);
$record->json = json_encode($data['json']);
$record->created_at = time();

global $DB;
$id = $DB->insert_record('railviewer_ebula_section', $record);

echo json_encode(['id' => $id]);

