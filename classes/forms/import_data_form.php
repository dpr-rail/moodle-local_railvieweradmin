<?php
namespace local_railvieweradmin\forms;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/** Upload-Formular des Daten-Imports (einzelne CSV oder ZIP-Paket). */
class import_data_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'importfile', get_string('import_file', 'local_railvieweradmin'), null,
            ['accepted_types' => ['.csv', '.txt', '.zip']]);
        $mform->addRule('importfile', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $types = ['auto' => get_string('import_type_auto', 'local_railvieweradmin')];
        foreach (\local_railvieweradmin\data_import::TYPES as $t) {
            $types[$t] = get_string('import_type_' . $t, 'local_railvieweradmin');
        }
        $mform->addElement('select', 'filetype', get_string('import_filetype', 'local_railvieweradmin'), $types);
        $mform->addHelpButton('filetype', 'import_filetype', 'local_railvieweradmin');

        $mform->addElement('select', 'delimiter', get_string('import_delimiter', 'local_railvieweradmin'), [
            'auto' => get_string('import_type_auto', 'local_railvieweradmin'), 'semicolon' => ';', 'comma' => ',', 'tab' => 'Tab']);
        $mform->addElement('select', 'encoding', get_string('import_encoding', 'local_railvieweradmin'), [
            'UTF-8' => 'UTF-8', 'ISO-8859-1' => 'ISO-8859-1', 'Windows-1252' => 'Windows-1252']);
        $this->add_action_buttons(false, get_string('import_preview', 'local_railvieweradmin'));
    }
}
