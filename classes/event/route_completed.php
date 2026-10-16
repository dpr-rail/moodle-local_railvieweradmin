<?php
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
