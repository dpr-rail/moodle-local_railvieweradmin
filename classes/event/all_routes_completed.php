<?php
namespace local_railvieweradmin\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Ein Nutzer hat alle ihm zugewiesenen Strecken in der geforderten Anzahl bearbeitet.
 *
 * relateduserid = betroffener Nutzer, other = [routecount].
 *
 * @package local_railvieweradmin
 */
class all_routes_completed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('event_all_routes_completed', 'local_railvieweradmin');
    }

    public function get_description() {
        return "The user with id '{$this->relateduserid}' completed all {$this->other['routecount']} assigned routes.";
    }

    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        if (!isset($this->other['routecount'])) {
            throw new \coding_exception('The \'routecount\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        return false;
    }
}
