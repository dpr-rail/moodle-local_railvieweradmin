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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Webservice-Funktionen von local_railvieweradmin (Strecken zuweisen, Fortschritt abrufen).
 *
 * @package local_railvieweradmin
 */
class local_railvieweradmin_external extends external_api {

    // ---- get_routes -------------------------------------------------------------------------

    public static function get_routes_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_routes() {
        global $DB;
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/railvieweradmin:assignroutes', $context);

        $routes = $DB->get_records('railviewer_routes', null, 'name ASC', 'id, name, pairedrouteid');
        $result = [];
        foreach ($routes as $r) {
            $result[] = [
                'id' => (int) $r->id,
                'name' => format_string($r->name),
                'pairedrouteid' => (int) ($r->pairedrouteid ?? 0),
            ];
        }
        return $result;
    }

    public static function get_routes_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Route id'),
            'name' => new external_value(PARAM_TEXT, 'Route name'),
            'pairedrouteid' => new external_value(PARAM_INT, 'Id of the opposite-direction route, 0 if none'),
        ]));
    }

    // ---- assign_routes ----------------------------------------------------------------------

    public static function assign_routes_parameters() {
        return new external_function_parameters([
            'assignments' => new external_multiple_structure(new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'Moodle user id'),
                'routeid' => new external_value(PARAM_INT, 'Route id'),
                'requiredviews' => new external_value(PARAM_INT, 'Required number of runs (ignored when learning methods are enabled)', VALUE_DEFAULT, 1),
                'experiencetypeid' => new external_value(PARAM_INT, 'Experience type id (required when learning methods are enabled)', VALUE_DEFAULT, 0),
                'waive' => new external_value(PARAM_BOOL, 'Waive the route for this user (counts as fulfilled)', VALUE_DEFAULT, 0),
                'waivereason' => new external_value(PARAM_TEXT, 'Reason, mandatory when waiving', VALUE_DEFAULT, ''),
            ])),
        ]);
    }

    public static function assign_routes($assignments) {
        global $DB;
        $params = self::validate_parameters(self::assign_routes_parameters(), ['assignments' => $assignments]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/railvieweradmin:assignroutes', $context);

        $learning = \local_railvieweradmin\learning::enabled();
        $results = [];
        foreach ($params['assignments'] as $a) {
            $status = 'created';
            $id = 0;
            if (!$learning && $a['requiredviews'] < 1) {
                $status = 'invalid_requiredviews';
            } else if (!$DB->record_exists('user', ['id' => $a['userid'], 'deleted' => 0])) {
                $status = 'unknown_user';
            } else if (!$DB->record_exists('railviewer_routes', ['id' => $a['routeid']])) {
                $status = 'unknown_route';
            } else if ($learning) {
                if (!$DB->record_exists('railviewer_experience_types', ['id' => $a['experiencetypeid'], 'enabled' => 1])) {
                    $status = 'unknown_experiencetype';
                } else if (!empty($a['waive']) && trim($a['waivereason']) === '') {
                    $status = 'waivereason_required';
                } else {
                    // Zusammenführen statt Überspringen: je Methode das Maximum (siehe learning::merge_assignment).
                    $res = \local_railvieweradmin\learning::merge_assignment((int) $a['userid'], (int) $a['routeid'],
                        (int) $a['experiencetypeid'], 1, !empty($a['waive']), trim($a['waivereason']), (int) $GLOBALS['USER']->id);
                    $status = $res['status'] === 'merged' ? 'updated' : $res['status'];
                    $id = $res['assignmentid'];
                }
            } else if ($existing = $DB->get_record('railviewer_assignments',
                    ['userid' => $a['userid'], 'routeid' => $a['routeid']])) {
                // Bereits zugewiesen: nur die geforderte Anzahl aktualisieren.
                $id = (int) $existing->id;
                if ((int) $existing->required_views !== $a['requiredviews']) {
                    $existing->required_views = $a['requiredviews'];
                    $DB->update_record('railviewer_assignments', $existing);
                    $status = 'updated';
                } else {
                    $status = 'exists';
                }
            } else {
                $id = $DB->insert_record('railviewer_assignments', (object) [
                    'userid' => $a['userid'],
                    'routeid' => $a['routeid'],
                    'required_views' => $a['requiredviews'],
                    'completed_views' => 0,
                ]);
            }
            $results[] = [
                'userid' => $a['userid'],
                'routeid' => $a['routeid'],
                'assignmentid' => (int) $id,
                'status' => $status,
            ];
        }
        return $results;
    }

    public static function assign_routes_returns() {
        return new external_multiple_structure(new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'routeid' => new external_value(PARAM_INT, 'Route id'),
            'assignmentid' => new external_value(PARAM_INT, 'Assignment id, 0 on error'),
            'status' => new external_value(PARAM_ALPHAEXT,
                'created | updated | exists | unknown_user | unknown_route | invalid_requiredviews | unknown_experiencetype | waivereason_required'),
        ]));
    }

    // ---- get_user_progress ------------------------------------------------------------------

    public static function get_user_progress_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Moodle user id'),
        ]);
    }

    public static function get_user_progress($userid) {
        global $DB;
        $params = self::validate_parameters(self::get_user_progress_parameters(), ['userid' => $userid]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/railvieweradmin:viewprogress', $context);

        $sql = "SELECT ra.*, r.name AS routename
                  FROM {railviewer_assignments} ra
                  JOIN {railviewer_routes} r ON r.id = ra.routeid
                 WHERE ra.userid = :userid
              ORDER BY r.name, ra.id";
        $rows = $DB->get_records_sql($sql, ['userid' => $params['userid']]);

        $assignments = [];
        $open = 0;
        $learning = \local_railvieweradmin\learning::enabled();
        foreach ($rows as $row) {
            $complete = \local_railvieweradmin\learning::is_complete($row);
            if (!$complete) {
                $open++;
            }
            $missing = [];
            if ($learning && empty($row->waived)) {
                foreach (\local_railvieweradmin\learning::status($row)['missing'] as $m) {
                    $missing[] = [
                        'sectionid' => (int) $m['sectionid'],
                        'sectionname' => $m['sectionname'],
                        'methodid' => (int) $m['methodid'],
                        'methodname' => $m['methodname'],
                        'count' => (int) $m['count'],
                    ];
                }
            }
            $assignments[] = [
                'assignmentid' => (int) $row->id,
                'routeid' => (int) $row->routeid,
                'routename' => format_string($row->routename),
                'requiredviews' => (int) $row->required_views,
                'completedviews' => (int) $row->completed_views,
                'iscomplete' => $complete,
                'lastviewed' => (int) $row->lastviewed,
                'waived' => !empty($row->waived),
                'experiencetypeid' => (int) ($row->experiencetypeid ?? 0),
                'missing' => $missing,
            ];
        }
        $declaration = 'notrequired';
        $ordercomplete = count($assignments) > 0 && $open === 0;
        $soll = 0;
        $ist = 0;
        if ($learning) {
            $overview = \local_railvieweradmin\learning::user_overview((int) $params['userid']);
            $declaration = \local_railvieweradmin\learning::declaration_status((int) $params['userid'], $overview['allcomplete']);
            $ordercomplete = \local_railvieweradmin\learning::order_complete((int) $params['userid'], $overview);
            $soll = $overview['soll'];
            $ist = $overview['ist'];
        }
        return [
            'userid' => (int) $params['userid'],
            'total' => count($assignments),
            'completed' => count($assignments) - $open,
            'allcomplete' => count($assignments) > 0 && $open === 0,
            'declaration' => $declaration,
            'ordercomplete' => $ordercomplete,
            'targetminutes' => (int) $soll,
            'doneminutes' => (int) $ist,
            'assignments' => $assignments,
        ];
    }

    public static function get_user_progress_returns() {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'total' => new external_value(PARAM_INT, 'Number of assigned routes'),
            'completed' => new external_value(PARAM_INT, 'Number of completed routes'),
            'allcomplete' => new external_value(PARAM_BOOL, 'True if at least one route is assigned and all are complete'),
            'declaration' => new external_value(PARAM_ALPHA, 'Completion declaration: notrequired | notyet | pending | declined | accepted'),
            'ordercomplete' => new external_value(PARAM_BOOL, 'Whole order done (all routes complete and, if required, declaration accepted)'),
            'targetminutes' => new external_value(PARAM_INT, 'Total target duration of the order in minutes (learning methods only)'),
            'doneminutes' => new external_value(PARAM_INT, 'Minutes already done (learning methods only)'),
            'assignments' => new external_multiple_structure(new external_single_structure([
                'assignmentid' => new external_value(PARAM_INT, 'Assignment id'),
                'routeid' => new external_value(PARAM_INT, 'Route id'),
                'routename' => new external_value(PARAM_TEXT, 'Route name'),
                'requiredviews' => new external_value(PARAM_INT, 'Required runs'),
                'completedviews' => new external_value(PARAM_INT, 'Completed runs'),
                'iscomplete' => new external_value(PARAM_BOOL, 'Required runs reached'),
                'lastviewed' => new external_value(PARAM_INT, 'Unix timestamp of last completed run, 0 if never'),
                'waived' => new external_value(PARAM_BOOL, 'Route waived for this user'),
                'experiencetypeid' => new external_value(PARAM_INT, 'Experience type id, 0 if none'),
                'missing' => new external_multiple_structure(new external_single_structure([
                    'sectionid' => new external_value(PARAM_INT, 'Section id'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Section name'),
                    'methodid' => new external_value(PARAM_INT, 'Learning method id'),
                    'methodname' => new external_value(PARAM_TEXT, 'Learning method name'),
                    'count' => new external_value(PARAM_INT, 'Missing runs'),
                ]), 'Missing runs per section and method (learning methods only)'),
            ])),
        ]);
    }

    // ---- get_methods / get_experience_types -------------------------------------------------

    public static function get_methods_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_methods() {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/railvieweradmin:assignroutes', $context);
        $result = [];
        foreach (\local_railvieweradmin\learning::methods() as $m) {
            $result[] = ['id' => (int) $m->id, 'name' => format_string($m->name), 'night' => (bool) $m->night];
        }
        return $result;
    }

    public static function get_methods_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Method id'),
            'name' => new external_value(PARAM_TEXT, 'Method name'),
            'night' => new external_value(PARAM_BOOL, 'Night ride'),
        ]));
    }

    public static function get_experience_types_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_experience_types() {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/railvieweradmin:assignroutes', $context);
        $result = [];
        foreach (\local_railvieweradmin\learning::experience_types() as $t) {
            $result[] = ['id' => (int) $t->id, 'name' => format_string($t->name)];
        }
        return $result;
    }

    public static function get_experience_types_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Experience type id'),
            'name' => new external_value(PARAM_TEXT, 'Experience type name'),
        ]));
    }
}
