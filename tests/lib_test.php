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
 * Unit-Tests für die Hin-/Rückfahrt-Verknüpfungs-Helper in lib.php.
 *
 * Deckt lokal_railvieweradmin_update_route_pairing() (DB-schreibend, symmetrische
 * Verknüpfung/Auflösung inkl. Race-Condition-Absicherung) und
 * local_railvieweradmin_group_by_route_pairing() (reine Gruppierungslogik ohne DB-Zugriff) ab -
 * siehe die ausführlichen Kommentare direkt bei den Funktionen in lib.php für die fachliche
 * Begründung des jeweiligen Verhaltens.
 *
 * @package    local_railvieweradmin
 * @copyright  2026 Railviewer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/railvieweradmin/lib.php');

class local_railvieweradmin_lib_testcase extends advanced_testcase {

    /**
     * Legt eine minimale Test-Strecke an (ohne Abschnitte - für die hier getesteten Helper
     * irrelevant, die operieren ausschließlich auf railviewer_routes selbst).
     *
     * @param string $name
     * @return int Neue routeid
     */
    protected function create_route($name) {
        global $DB;
        return $DB->insert_record('railviewer_routes', (object) [
            'name' => $name,
            'description' => '',
            'pairedrouteid' => null,
            'direction' => null,
        ]);
    }

    protected function get_route($routeid) {
        global $DB;
        return $DB->get_record('railviewer_routes', ['id' => $routeid], '*', MUST_EXIST);
    }

    // ------------------------------------------------------------------
    // local_railvieweradmin_update_route_pairing()
    // ------------------------------------------------------------------

    public function test_update_route_pairing_links_symmetrically() {
        $this->resetAfterTest(true);

        $hin = $this->create_route('Hinfahrt KK-KKW');
        $rueck = $this->create_route('Rückfahrt KKW-KK');

        local_railvieweradmin_update_route_pairing($hin, $rueck, 'hin');

        $hinrecord = $this->get_route($hin);
        $rueckrecord = $this->get_route($rueck);

        $this->assertEquals($rueck, $hinrecord->pairedrouteid);
        $this->assertEquals('hin', $hinrecord->direction);
        // Gegenseite bekommt automatisch die jeweils andere Richtung - ein inkonsistentes
        // hin/hin-Paar darf strukturell nicht entstehen können.
        $this->assertEquals($hin, $rueckrecord->pairedrouteid);
        $this->assertEquals('rueck', $rueckrecord->direction);
    }

    public function test_update_route_pairing_direction_rueck_gives_partner_hin() {
        $this->resetAfterTest(true);

        $a = $this->create_route('Route A');
        $b = $this->create_route('Route B');

        local_railvieweradmin_update_route_pairing($a, $b, 'rueck');

        $this->assertEquals('rueck', $this->get_route($a)->direction);
        $this->assertEquals('hin', $this->get_route($b)->direction);
    }

    public function test_update_route_pairing_unlink() {
        $this->resetAfterTest(true);

        $hin = $this->create_route('Hinfahrt');
        $rueck = $this->create_route('Rückfahrt');
        local_railvieweradmin_update_route_pairing($hin, $rueck, 'hin');

        // 0 als neuer Partner löst die Verknüpfung; $direction wird dabei ignoriert.
        local_railvieweradmin_update_route_pairing($hin, 0, null);

        $hinrecord = $this->get_route($hin);
        $rueckrecord = $this->get_route($rueck);

        $this->assertNull($hinrecord->pairedrouteid);
        $this->assertNull($hinrecord->direction);
        // Der ehemalige Partner wird zu einer normalen, unverknüpften Strecke - NIE mitgelöscht,
        // nur die Verknüpfung selbst wird aufgelöst.
        $this->assertNull($rueckrecord->pairedrouteid);
        $this->assertNull($rueckrecord->direction);
    }

    public function test_update_route_pairing_resolves_previous_partner_when_repaired() {
        $this->resetAfterTest(true);

        $a = $this->create_route('Route A');
        $b = $this->create_route('Route B');
        $c = $this->create_route('Route C');

        local_railvieweradmin_update_route_pairing($a, $b, 'hin');
        // A wird stattdessen mit C verknüpft - die alte Verknüpfung zu B muss sauber aufgelöst
        // werden (B darf NICHT weiterhin auf A zeigen, sonst wäre B "verwaist" mit einem
        // pairedrouteid, dessen Gegenstück gar nicht mehr auf B zurückzeigt).
        local_railvieweradmin_update_route_pairing($a, $c, 'hin');

        $arecord = $this->get_route($a);
        $brecord = $this->get_route($b);
        $crecord = $this->get_route($c);

        $this->assertEquals($c, $arecord->pairedrouteid);
        $this->assertNull($brecord->pairedrouteid, 'Alter Partner B muss entkoppelt werden');
        $this->assertNull($brecord->direction);
        $this->assertEquals($a, $crecord->pairedrouteid);
    }

    public function test_update_route_pairing_resolves_new_partners_existing_link() {
        $this->resetAfterTest(true);

        $a = $this->create_route('Route A');
        $b = $this->create_route('Route B');
        $c = $this->create_route('Route C');

        // B ist bereits mit C verknüpft ...
        local_railvieweradmin_update_route_pairing($b, $c, 'hin');
        // ... jetzt wird A stattdessen mit B verknüpft. Der eindeutige pairedrouteid_fk-Key
        // (1:1) würde einen widersprüchlichen DB-Zustand ablehnen, wenn B weiterhin gleichzeitig
        // auf C zeigen würde - die Funktion muss das selbst vorher auflösen (Race-Condition-
        // Absicherung laut Kommentar in lib.php), nicht einfach einen DB-Fehler werfen lassen.
        local_railvieweradmin_update_route_pairing($a, $b, 'hin');

        $arecord = $this->get_route($a);
        $brecord = $this->get_route($b);
        $crecord = $this->get_route($c);

        $this->assertEquals($b, $arecord->pairedrouteid);
        $this->assertEquals($a, $brecord->pairedrouteid);
        $this->assertNull($crecord->pairedrouteid, 'C muss entkoppelt werden, sobald B anderweitig verknüpft wird');
    }

    public function test_update_route_pairing_repairing_same_partner_is_noop_safe() {
        $this->resetAfterTest(true);

        $a = $this->create_route('Route A');
        $b = $this->create_route('Route B');

        local_railvieweradmin_update_route_pairing($a, $b, 'hin');
        // Erneutes Verknüpfen mit demselben Partner (z.B. Admin speichert das Formular zweimal,
        // ohne etwas zu ändern) darf nicht zum Auflösen und "verwaisen" führen.
        local_railvieweradmin_update_route_pairing($a, $b, 'hin');

        $this->assertEquals($b, $this->get_route($a)->pairedrouteid);
        $this->assertEquals($a, $this->get_route($b)->pairedrouteid);
    }

    // ------------------------------------------------------------------
    // local_railvieweradmin_group_by_route_pairing()
    // ------------------------------------------------------------------

    public function test_group_by_route_pairing_groups_pair_when_both_present() {
        $items = [
            (object) ['routeid' => 1, 'pairedrouteid' => 2, 'direction' => 'hin'],
            (object) ['routeid' => 2, 'pairedrouteid' => 1, 'direction' => 'rueck'],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertCount(1, $groups);
        $this->assertEquals('pair', $groups[0]['type']);
        // Konsistente Reihenfolge [hin, rueck], unabhängig von der Reihenfolge in $items.
        $this->assertEquals(1, $groups[0]['items'][0]->routeid);
        $this->assertEquals(2, $groups[0]['items'][1]->routeid);
    }

    public function test_group_by_route_pairing_order_independent_of_input_order() {
        // Rückfahrt zuerst in der Eingabeliste - die Ausgabe muss trotzdem [hin, rueck] sein.
        $items = [
            (object) ['routeid' => 2, 'pairedrouteid' => 1, 'direction' => 'rueck'],
            (object) ['routeid' => 1, 'pairedrouteid' => 2, 'direction' => 'hin'],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertEquals(1, $groups[0]['items'][0]->routeid);
        $this->assertEquals(2, $groups[0]['items'][1]->routeid);
    }

    public function test_group_by_route_pairing_stays_single_when_partner_missing_from_list() {
        // Strecke 1 ist verknüpft mit Strecke 2, aber Strecke 2 ist in dieser Liste gar nicht
        // enthalten (z.B. ein Nutzer hat bislang nur eine Richtung zugewiesen bekommen) - die
        // Paarung auf Strecken-Ebene darf hier NICHT erzwingen, dass beide vorhanden sein müssen.
        $items = [
            (object) ['routeid' => 1, 'pairedrouteid' => 2, 'direction' => 'hin'],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertCount(1, $groups);
        $this->assertEquals('single', $groups[0]['type']);
        $this->assertEquals(1, $groups[0]['item']->routeid);
    }

    public function test_group_by_route_pairing_unlinked_routes_stay_single() {
        $items = [
            (object) ['routeid' => 1, 'pairedrouteid' => null, 'direction' => null],
            (object) ['routeid' => 2, 'pairedrouteid' => null, 'direction' => null],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertCount(2, $groups);
        $this->assertEquals('single', $groups[0]['type']);
        $this->assertEquals('single', $groups[1]['type']);
    }

    public function test_group_by_route_pairing_accepts_array_items() {
        // Die Funktion muss sowohl stdClass- als auch Array-Elemente lesen können (siehe
        // $get()-Closure in lib.php) - Aufrufer übergeben je nach Kontext das eine oder andere.
        $items = [
            ['routeid' => 1, 'pairedrouteid' => 2, 'direction' => 'hin'],
            ['routeid' => 2, 'pairedrouteid' => 1, 'direction' => 'rueck'],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertCount(1, $groups);
        $this->assertEquals('pair', $groups[0]['type']);
    }

    public function test_group_by_route_pairing_preserves_order_of_first_occurrence() {
        $items = [
            (object) ['routeid' => 3, 'pairedrouteid' => null, 'direction' => null],
            (object) ['routeid' => 1, 'pairedrouteid' => 2, 'direction' => 'hin'],
            (object) ['routeid' => 2, 'pairedrouteid' => 1, 'direction' => 'rueck'],
        ];

        $groups = local_railvieweradmin_group_by_route_pairing($items);

        $this->assertCount(2, $groups);
        $this->assertEquals('single', $groups[0]['type']);
        $this->assertEquals(3, $groups[0]['item']->routeid);
        $this->assertEquals('pair', $groups[1]['type']);
    }
}
