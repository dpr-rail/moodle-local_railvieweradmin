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

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/** Upload-Formular des CSV-Imports von Abschnittsnamen. */
class import_sections_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'csvfile', get_string('import_csvfile', 'local_railvieweradmin'), null,
            ['accepted_types' => ['.csv', '.txt']]);
        $mform->addRule('csvfile', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');
        $mform->addElement('select', 'delimiter_name', get_string('import_delimiter', 'local_railvieweradmin'), [
            'semicolon' => ';', 'comma' => ',', 'tab' => 'Tab',
        ]);
        $mform->setDefault('delimiter_name', 'semicolon');
        $mform->addElement('select', 'encoding', get_string('import_encoding', 'local_railvieweradmin'), [
            'UTF-8' => 'UTF-8', 'ISO-8859-1' => 'ISO-8859-1', 'Windows-1252' => 'Windows-1252',
        ]);
        $mform->setDefault('encoding', 'UTF-8');
        $this->add_action_buttons(false, get_string('import_preview', 'local_railvieweradmin'));
    }
}
