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

class overlay_form extends \moodleform {

    public function definition() {
        global $DB;
        $mform = $this->_form;
        // Formular-ID setzen
        $mform->updateAttributes(['id' => 'overlay-form']);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('textarea', 'name', get_string('overlay_name', 'local_railvieweradmin'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        // Auswahl Abschnitt (railviewer_sections)
        $sections = $DB->get_records_menu('railviewer_sections', [], 'name ASC', 'id, name');
        $mform->addElement('select', 'sectionid', get_string('section', 'local_railvieweradmin'), $sections);
        $mform->setType('sectionid', PARAM_INT);

        $mform->addElement('text', 'starttime', get_string('start_time', 'local_railvieweradmin'), ['size' => 10]);
        $mform->setType('starttime', PARAM_INT);
        $mform->addRule('starttime', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $mform->addElement('text', 'endtime', get_string('end_time', 'local_railvieweradmin'), ['size' => 10]);
        $mform->setType('endtime', PARAM_INT);
        $mform->addRule('endtime', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $mform->addElement('text', 'posx', get_string('position_x', 'local_railvieweradmin'), ['size' => 10]);
        $mform->setType('posx', PARAM_INT);

        $mform->addElement('text', 'posy', get_string('position_y', 'local_railvieweradmin'), ['size' => 10]);
        $mform->setType('posy', PARAM_INT);

        $mform->addElement('button', 'toggleadvanced', get_string('show_advanced_options', 'local_railvieweradmin'), ['id' => 'toggleadvanced']);
    
        // Container Div für optionale Felder
        $mform->addElement('html', '
            <div id="advancedoptions" style="display:none; margin-top: 15px;">
        ');

        // Schriftgröße
        $fontsizeoptions = [];
        for ($i = 6; $i <= 20; $i++) {
            $fontsizeoptions[$i] = get_string('font_size_px', 'local_railvieweradmin', $i);
        }
        $mform->addElement('select', 'fontsize', get_string('font_size', 'local_railvieweradmin'), $fontsizeoptions);
        $mform->setType('fontsize', PARAM_INT);

        // Schriftfarbe
        $mform->addElement('text', 'fontcolor', get_string('font_color', 'local_railvieweradmin'), ['size' => 7, 'maxlength' => 7]);
        $mform->setType('fontcolor', PARAM_TEXT);

        // Hintergrundfarbe
        $mform->addElement('text', 'backgroundcolor', get_string('background_color', 'local_railvieweradmin'), ['size' => 7, 'maxlength' => 7]);
        $mform->setType('backgroundcolor', PARAM_TEXT);

        // Rahmenfarbe
        $mform->addElement('text', 'bordercolor', get_string('border_color', 'local_railvieweradmin'), ['size' => 7, 'maxlength' => 7]);
        $mform->setType('bordercolor', PARAM_TEXT);

        // Position Marker (Select)
        $markerpositions = [
            'none' => get_string('no_arrow', 'local_railvieweradmin'),
            'top-left' => get_string('top_left', 'local_railvieweradmin'),
            'top' => get_string('top', 'local_railvieweradmin'),
            'top-right' => get_string('top_right', 'local_railvieweradmin'),
            'right' => get_string('right', 'local_railvieweradmin'),
            'bottom-right' => get_string('bottom_right', 'local_railvieweradmin'),
            'bottom' => get_string('bottom', 'local_railvieweradmin'),
            'bottom-left' => get_string('bottom_left', 'local_railvieweradmin'),
            'left' => get_string('left', 'local_railvieweradmin'),
        ];
        $mform->addElement('select', 'markerposition', get_string('marker_position', 'local_railvieweradmin'), $markerpositions);
        $mform->setType('markerposition', PARAM_ALPHANUMEXT);

        // Feste Breite in %
        $mform->addElement('text', 'fixedboxwidth', get_string('fixed_bubble_width', 'local_railvieweradmin'), ['size' => 5]);
        $mform->setType('fixedboxwidth', PARAM_INT);

        // Standardwerte aus Plugin-Settings setzen (falls übergeben)
        if (!empty($this->_customdata['defaults'])) {
            foreach ($this->_customdata['defaults'] as $field => $value) {
                $mform->setDefault($field, $value);
            }
        }
        $mform->addElement('html', '</div>');
        
        // Action Buttons
        $this->add_action_buttons();

        $mform->addElement('html', '
            <script>
            document.addEventListener("DOMContentLoaded", () => {
                const pairs = [
                    {text: "id_fontcolor", color: "id_fontcolorpicker"},
                    {text: "id_backgroundcolor", color: "id_backgroundcolorpicker"},
                    {text: "id_bordercolor", color: "id_bordercolorpicker"},
                ];

                pairs.forEach(({text, color}) => {
                    const textInput = document.getElementById(text);
                    const colorInput = document.getElementById(color);
                    if (!textInput || !colorInput) return;

                    if (textInput.value) {
                        colorInput.value = textInput.value;
                    } else {
                        textInput.value = colorInput.value;
                    }

                    colorInput.addEventListener("input", () => {
                        textInput.value = colorInput.value;
                    });

                    textInput.addEventListener("input", () => {
                        if(/^#([0-9A-F]{6}|[0-9A-F]{3})$/i.test(textInput.value)) {
                            colorInput.value = textInput.value;
                        }
                    });
                });
            });
            </script>
        ');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['starttime'] > $data['endtime']) {
            $errors['endtime'] = get_string('end_time_validation', 'local_railvieweradmin');
        }

        // fixedboxwidth darf leer sein, oder 0 bis 100
        if ($data['fixedboxwidth'] !== '' && ($data['fixedboxwidth'] < 0 || $data['fixedboxwidth'] > 100)) {
            $errors['fixedboxwidth'] = get_string('fixed_width_validation', 'local_railvieweradmin');
        }

        return $errors;
    }
}
