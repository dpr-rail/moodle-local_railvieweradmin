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

class assignment_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Bei einer neuen Zuweisung dürfen mehrere Nutzer auf einmal ausgewählt werden (legt
        // dann eine Zuweisung pro Nutzer an, siehe edit_assignment.php); beim Bearbeiten einer
        // bestehenden Zuweisung bleibt es bei genau einem Nutzer, da die Zeile schon existiert.
        $isnew = empty($this->_customdata['data']->id);

        // User Auswahl: nur aktive Nutzer (nicht gelöscht, nicht gesperrt, kein Gast), als
        // durchsuchbares Autocomplete-Feld statt einer langen Dropdown-Liste - Moodles
        // Autocomplete filtert clientseitig automatisch über den angezeigten Text, also
        // sowohl Vor- als auch Nachname.
        global $DB, $CFG;
        $users = [];
        $allusers = $DB->get_records_select(
            'user',
            'deleted = 0 AND suspended = 0 AND id <> ?',
            [$CFG->siteguest],
            'lastname ASC, firstname ASC'
        );
        foreach ($allusers as $user) {
            $users[$user->id] = fullname($user);
        }
        $userlabel = $isnew ? get_string('users', 'local_railvieweradmin') : get_string('user', 'local_railvieweradmin');
        $mform->addElement('autocomplete', 'userid', $userlabel, $users, [
            'noselectionstring' => get_string('choosedots'),
            'multiple' => $isnew,
        ]);
        $mform->setType('userid', PARAM_INT);
        $mform->addRule('userid', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        // Strecke Auswahl: bei einer neuen Zuweisung dürfen mehrere Strecken/Orte auf einmal
        // ausgewählt werden (eine Zuweisung pro Strecke je Nutzer, siehe edit_assignment.php) -
        // dieselbe Logik wie schon bei der Nutzerauswahl oben. Beim Bearbeiten einer
        // bestehenden Zuweisung bleibt es bei genau einer Strecke, da die Zeile schon existiert.
        $routes = [];
        $allroutes = $DB->get_records('railviewer_routes', null, 'name ASC');
        foreach ($allroutes as $route) {
            $routes[$route->id] = format_string($route->name);
        }

        $routelabel = $isnew ? get_string('routes', 'local_railvieweradmin') : get_string('route', 'local_railvieweradmin');
        if ($isnew) {
            $mform->addElement('autocomplete', 'routeid', $routelabel, $routes, [
                'noselectionstring' => get_string('choosedots'),
                'multiple' => true,
            ]);
        } else {
            $mform->addElement('select', 'routeid', $routelabel, $routes);
        }
        $mform->setType('routeid', PARAM_INT);
        // Bei neuer Zuweisung KEIN 'required' hier - stattdessen wird in validation() geprüft,
        // dass mindestens eine Strecke ODER eine Einsatzstelle gewählt wurde (siehe unten).
        if (!$isnew) {
            $mform->addRule('routeid', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');
        }

        if ($isnew) {
            // Einsatzstelle statt/zusätzlich zu einzelnen Strecken: buendelt mehrere
            // Strecken/Orte, damit ein Admin sie nicht jedes Mal einzeln zusammensuchen muss.
            $einsatzstellen = [0 => get_string('no_einsatzstelle', 'local_railvieweradmin')];
            $alleinsatzstellen = $DB->get_records('railviewer_einsatzstellen', null, 'name ASC');
            foreach ($alleinsatzstellen as $es) {
                $einsatzstellen[$es->id] = format_string($es->name);
            }
            $mform->addElement('select', 'einsatzstelleid', get_string('einsatzstelle', 'local_railvieweradmin'), $einsatzstellen);
            $mform->setType('einsatzstelleid', PARAM_INT);
            $mform->addHelpButton('einsatzstelleid', 'einsatzstelle_assign', 'local_railvieweradmin');
        }

        // Bequemlichkeit bei neuer Zuweisung: falls eine gewählte Strecke eine verknüpfte
        // Gegenrichtung hat, diese optional in einem Schritt mitzuweisen statt zwei separate
        // Zuweisungsvorgänge zu brauchen. Bewusst per Default AUS (Opt-in), damit niemand
        // versehentlich doppelt zuweist, der die Checkbox übersieht.
        if ($isnew) {
            $mform->addElement('advcheckbox', 'assignbothdirections', '',
                get_string('assign_both_directions', 'local_railvieweradmin'));
            $mform->setType('assignbothdirections', PARAM_BOOL);
            $mform->setDefault('assignbothdirections', 0);
            $mform->addHelpButton('assignbothdirections', 'assign_both_directions', 'local_railvieweradmin');
        }

        $learning = \local_railvieweradmin\learning::enabled();
        if ($learning && $isnew) {
            // Lernmethoden-Feature: die geforderte Häufigkeit ergibt sich aus der Anforderungsmatrix
            // der Strecke und dem hier gewählten Berufserfahrungstyp (siehe route_requirements.php).
            $typeoptions = [0 => get_string('choosedots')];
            foreach (\local_railvieweradmin\learning::experience_types() as $t) {
                $typeoptions[$t->id] = format_string($t->name);
            }
            $mform->addElement('select', 'experiencetypeid', get_string('experience_type', 'local_railvieweradmin'), $typeoptions);
            $mform->setType('experiencetypeid', PARAM_INT);
            $mform->addHelpButton('experiencetypeid', 'experience_type', 'local_railvieweradmin');
            $mform->addElement('hidden', 'required_views');
            $mform->setType('required_views', PARAM_INT);
            $mform->setDefault('required_views', 1);
        } else {
            $mform->addElement('text', 'required_views', get_string('required_views', 'local_railvieweradmin'), ['size' => 5]);
            $mform->setType('required_views', PARAM_INT);
            $mform->setDefault('required_views', 1);
            $mform->addRule('required_views', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');
            $mform->addRule('required_views', get_string('must_be_positive_number', 'local_railvieweradmin'), 'numeric', null, 'client');
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $learning = \local_railvieweradmin\learning::enabled();
        $isnew0 = empty($this->_customdata['data']->id);
        if ($learning && $isnew0) {
            if (empty($data['experiencetypeid'])) {
                $errors['experiencetypeid'] = get_string('required_field', 'local_railvieweradmin');
            }
        } else if ($data['required_views'] <= 0) {
            $errors['required_views'] = get_string('must_be_positive_number', 'local_railvieweradmin');
        }

        // Bei neuer Zuweisung: mindestens eine Strecke/ein Ort ODER eine Einsatzstelle muss
        // gewählt sein (routeid trägt hier absichtlich keine 'required'-Regel, siehe definition()).
        $isnew = empty($this->_customdata['data']->id);
        if ($isnew && empty($data['routeid']) && empty($data['einsatzstelleid'])) {
            $errors['routeid'] = get_string('select_route_or_einsatzstelle', 'local_railvieweradmin');
        }

        return $errors;
    }
}
