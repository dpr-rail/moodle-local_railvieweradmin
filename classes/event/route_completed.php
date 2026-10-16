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

namespace local_railvieweradmin\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Ein Nutzer hat die geforderte Anzahl Durchläufe einer ihm zugewiesenen Strecke erreicht.
 *
 * objectid = railviewer_assignments.id, relateduserid = betroffener Nutzer,
 * other = [routeid, requiredviews, completedviews].
 *
 * @package local_railvieweradmin
 */
class route_completed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'railviewer_assignments';
    }

    public static function get_name() {
        return get_string('event_route_completed', 'local_railvieweradmin');
    }

    public function get_description() {
        return "The user with id '{$this->relateduserid}' completed the assigned route with id "
            . "'{$this->other['routeid']}' ({$this->other['completedviews']}/{$this->other['requiredviews']} runs).";
    }

    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        foreach (['routeid', 'requiredviews', 'completedviews'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '$key' value must be set in other.");
            }
        }
    }

    public static function get_objectid_mapping() {
        return ['db' => 'railviewer_assignments', 'restore' => \core\event\base::NOT_MAPPED];
    }

    public static function get_other_mapping() {
        return false;
    }
}
