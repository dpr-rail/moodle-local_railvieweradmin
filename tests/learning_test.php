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
 * Unit-Tests für das Lernmethoden-Feature: Fortschritt je Abschnitt/Methode, Ersetzung,
 * Zusammenführen von Zuweisungen und Soll-Dauer.
 *
 * @package    local_railvieweradmin
 * @copyright  2026 Railviewer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/railvieweradmin/lib.php');

/**
 * @group local_railvieweradmin
 * @covers \local_railvieweradmin\learning
 */
class local_railvieweradmin_learning_testcase extends advanced_testcase {

    /** @var stdClass[] Abschnitte A, B, C */
    private $sections;
    /** @var stdClass */
    private $route;
    /** @var stdClass */
    private $user;
    /** @var int[] shortname => methodid */
    private $m;
    /** @var int */
    private $typeid;

    protected function setUp() {
        global $DB;
        $this->resetAfterTest();
        set_config('learning_methods_enabled', 1, 'local_railvieweradmin');
        \local_railvieweradmin\learning::seed_defaults();
        $this->m = $DB->get_records_menu('railviewer_methods', null, '', 'shortname, id');
        $this->typeid = (int) $DB->get_field('railviewer_experience_types', 'id', [], IGNORE_MULTIPLE);
        $this->user = $this->getDataGenerator()->create_user();

        $this->route = (object) ['id' => $DB->insert_record('railviewer_routes', (object) ['name' => 'Testroute'])];
        $this->sections = [];
        foreach (['A', 'B', 'C'] as $i => $name) {
            $id = $DB->insert_record('railviewer_sections', (object) ['name' => $name, 'duration' => 600, 'video_source' => 'upload']);
            $DB->insert_record('railviewer_route_sections', (object) ['routeid' => $this->route->id, 'sectionid' => $id, 'sortorder' => $i]);
            $this->sections[$name] = (int) $id;
        }
    }

    private function require_method(string $short, int $count) {
        global $DB;
        $DB->insert_record('railviewer_route_reqs', (object) [
            'routeid' => $this->route->id, 'methodid' => $this->m[$short],
            'experiencetypeid' => $this->typeid, 'required_count' => $count,
        ]);
    }

    private function assign(): stdClass {
        global $DB;
        $res = \local_railvieweradmin\learning::merge_assignment($this->user->id, $this->route->id, $this->typeid, 0);
        return $DB->get_record('railviewer_assignments', ['id' => $res['assignmentid']]);
    }

    private function ride(stdClass $assignment, string $short, array $sectionnames, string $status = 'confirmed') {
        global $DB;
        $rideid = $DB->insert_record('railviewer_rides', (object) [
            'assignmentid' => $assignment->id, 'userid' => $this->user->id, 'routeid' => $this->route->id,
            'methodid' => $this->m[$short], 'ridedate' => time(), 'trainnumber' => '123',
            'fromsectionid' => $this->sections[reset($sectionnames)], 'tosectionid' => $this->sections[end($sectionnames)],
            'status' => $status, 'isexternal' => 0, 'timecreated' => time(),
        ]);
        foreach ($sectionnames as $name) {
            $DB->insert_record('railviewer_ride_sections', (object) ['rideid' => $rideid, 'sectionid' => $this->sections[$name]]);
        }
    }

    public function test_partial_ride_counts_only_for_its_sections() {
        $this->require_method('selfdrive', 1);
        $a = $this->assign();
        $this->ride($a, 'selfdrive', ['A', 'B']);
        $st = \local_railvieweradmin\learning::status($a);
        $mid = $this->m['selfdrive'];
        $this->assertTrue($st['sections'][$this->sections['A']]['cells'][$mid]['complete']);
        $this->assertTrue($st['sections'][$this->sections['B']]['cells'][$mid]['complete']);
        $this->assertFalse($st['sections'][$this->sections['C']]['cells'][$mid]['complete']);
        $this->assertFalse($st['complete']);
        $this->assertCount(1, $st['missing']);
    }

    public function test_full_ride_completes_and_pending_or_rejected_do_not_count() {
        $this->require_method('selfdrive', 1);
        $a = $this->assign();
        $this->ride($a, 'selfdrive', ['A', 'B', 'C'], 'pending');
        $this->ride($a, 'selfdrive', ['A', 'B', 'C'], 'rejected');
        $this->assertFalse(\local_railvieweradmin\learning::status($a)['complete']);
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->assertTrue(\local_railvieweradmin\learning::status($a)['complete']);
    }

    public function test_substitution_uses_surplus_only_and_requires_activation() {
        global $DB;
        $this->require_method('selfdrive_night', 1);
        $a = $this->assign();
        $DB->insert_record('railviewer_route_substs', (object) [
            'routeid' => $this->route->id, 'methodid' => $this->m['selfdrive_night'], 'bymethodid' => $this->m['selfdrive'],
            'factor' => 2, 'sortorder' => 0, 'userchoice' => 1,
        ]);
        // Zwei Tagfahrten über alles (keine Tag-Anforderung -> beide sind Überschuss).
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->assertFalse(\local_railvieweradmin\learning::status($a)['complete'], 'ohne Aktivierung kein Ersatz');

        $rule = $DB->get_record('railviewer_route_substs', ['routeid' => $this->route->id]);
        $DB->insert_record('railviewer_assignment_subst', (object) ['assignmentid' => $a->id, 'substid' => $rule->id, 'applied' => 1, 'timemodified' => time()]);
        $st = \local_railvieweradmin\learning::status($a);
        $this->assertTrue($st['complete']);
        $this->assertSame(1, $st['sections'][$this->sections['A']]['cells'][$this->m['selfdrive_night']]['substituted']);
    }

    public function test_substitution_does_not_use_rides_needed_for_own_requirement() {
        global $DB;
        $this->require_method('selfdrive', 2);
        $this->require_method('selfdrive_night', 1);
        $a = $this->assign();
        $DB->insert_record('railviewer_route_substs', (object) [
            'routeid' => $this->route->id, 'methodid' => $this->m['selfdrive_night'], 'bymethodid' => $this->m['selfdrive'],
            'factor' => 2, 'sortorder' => 0, 'userchoice' => 0,
        ]);
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->assertFalse(\local_railvieweradmin\learning::status($a)['complete'], 'Pflichtfahrten dürfen nicht ersetzen');
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        $this->assertTrue(\local_railvieweradmin\learning::status($a)['complete']);
    }

    public function test_merge_takes_maximum_never_sum() {
        $this->require_method('selfdrive', 1);
        $a = $this->assign();
        $second = (int) \local_railvieweradmin\learning::merge_assignment($this->user->id, $this->route->id, $this->typeid, 0)['assignmentid'];
        $this->assertEquals($a->id, $second);
        global $DB;
        $DB->set_field('railviewer_route_reqs', 'required_count', 3, ['routeid' => $this->route->id]);
        $res = \local_railvieweradmin\learning::merge_assignment($this->user->id, $this->route->id, $this->typeid, 0);
        $this->assertSame('merged', $res['status']);
        $this->assertEquals(3, $DB->get_field('railviewer_assignment_req', 'required_count', ['assignmentid' => $a->id]));
        $res = \local_railvieweradmin\learning::merge_assignment($this->user->id, $this->route->id, $this->typeid, 0);
        $this->assertSame('exists', $res['status']);
    }

    public function test_waived_counts_as_complete_and_has_no_duration() {
        $this->require_method('selfdrive', 1);
        $res = \local_railvieweradmin\learning::merge_assignment($this->user->id, $this->route->id, $this->typeid, 0, true, 'kennt die Strecke');
        global $DB;
        $a = $DB->get_record('railviewer_assignments', ['id' => $res['assignmentid']]);
        $this->assertTrue(\local_railvieweradmin\learning::is_complete($a));
        $this->assertSame(0, \local_railvieweradmin\learning::duration($a)['soll']);
    }

    public function test_duration_is_per_section_and_partial() {
        global $DB;
        $this->require_method('selfdrive', 2);
        $DB->insert_record('railviewer_section_durations', (object) ['sectionid' => $this->sections['A'], 'methodid' => $this->m['selfdrive'], 'minutes' => 30]);
        $DB->insert_record('railviewer_section_durations', (object) ['sectionid' => $this->sections['B'], 'methodid' => $this->m['selfdrive'], 'minutes' => 20]);
        $DB->insert_record('railviewer_section_durations', (object) ['sectionid' => $this->sections['C'], 'methodid' => $this->m['selfdrive'], 'minutes' => 10]);
        $a = $this->assign();
        $this->assertSame(2 * 60, \local_railvieweradmin\learning::duration($a)['soll']);
        $this->ride($a, 'selfdrive', ['A', 'B']); // Teilfahrt: nur 50 min
        $this->assertSame(50, \local_railvieweradmin\learning::duration($a)['ist']);
    }

    public function test_declaration_required_before_order_is_complete() {
        global $DB;
        set_config('declaration_enabled', 1, 'local_railvieweradmin');
        $this->require_method('selfdrive', 1);
        $a = $this->assign();
        $this->ride($a, 'selfdrive', ['A', 'B', 'C']);
        local_railvieweradmin_sync_assignment_progress($a->id);
        $this->assertSame('pending', \local_railvieweradmin\learning::declaration_status($this->user->id));
        $this->assertFalse(\local_railvieweradmin\learning::order_complete($this->user->id));
        \local_railvieweradmin\learning::answer_declaration($this->user->id, false, 'noch nicht');
        $this->assertSame('declined', \local_railvieweradmin\learning::declaration_status($this->user->id));
        \local_railvieweradmin\learning::answer_declaration($this->user->id, true);
        $this->assertTrue(\local_railvieweradmin\learning::order_complete($this->user->id));
    }
}
