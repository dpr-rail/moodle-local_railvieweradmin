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

namespace local_railvieweradmin\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Formular für eine "Einsatzstelle": eine benannte Gruppe aus mehreren Strecken/Orten
 * (railviewer_routes-Zeilen, inkl. der "Ortskenntnis"-Pseudo-Routen), die einem Nutzer bei der
 * Zuweisung in einem Schritt zugewiesen werden kann, statt jede Strecke einzeln auszuwählen.
 */
class einsatzstelle_form extends \moodleform {

    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'local_railvieweradmin'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'local_railvieweradmin'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Durchsuchbares Autocomplete statt einer langen Dropdown-Liste, analog zur
        // Nutzerauswahl im Zuweisungsformular - bei vielen Strecken sonst unübersichtlich.
        $routes = [];
        $allroutes = $DB->get_records('railviewer_routes', null, 'name ASC');
        foreach ($allroutes as $route) {
            $routes[$route->id] = format_string($route->name);
        }
        $mform->addElement('autocomplete', 'routes', get_string('einsatzstelle_routes', 'local_railvieweradmin'), $routes, [
            'multiple' => true,
            'noselectionstring' => get_string('choosedots'),
        ]);
        $mform->setType('routes', PARAM_INT);
        $mform->addRule('routes', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['routes'])) {
            $errors['routes'] = get_string('select_at_least_one_route', 'local_railvieweradmin');
        }
        return $errors;
    }
}
