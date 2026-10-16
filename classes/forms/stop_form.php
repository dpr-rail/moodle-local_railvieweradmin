<?php
namespace local_railvieweradmin\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class stop_form extends \moodleform {

    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('stop_name', 'local_railvieweradmin'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        // Auswahl Abschnitt (railviewer_sections)
        $sections = $DB->get_records_menu('railviewer_sections', [], 'name ASC', 'id, name');
        $mform->addElement('select', 'sectionid', get_string('section', 'local_railvieweradmin'), $sections);
        $mform->setType('sectionid', PARAM_INT);

        $mform->addElement('text', 'stoptime', get_string('stop_time_seconds', 'local_railvieweradmin'), ['size' => 10]);
        $mform->setType('stoptime', PARAM_INT);
        $mform->addRule('stoptime', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['stoptime'] < 0) {
            $errors['stoptime'] = get_string('stoptime_must_be_positive', 'local_railvieweradmin');
        }
        return $errors;
    }
}
