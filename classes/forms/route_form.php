<?php
namespace local_railvieweradmin\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class route_form extends \moodleform {

    protected $selectedsections;

    public function definition() {
        global $DB;
        $mform = $this->_form;

        $this->selectedsections = $this->_customdata['selectedsections'] ?? [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'local_railvieweradmin'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'local_railvieweradmin'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Abschnitte als Multi-Select mit Drag&Drop Reihenfolge
        $sections = $DB->get_records_menu('railviewer_sections', [], 'name ASC', 'id, name');
        $mform->addElement('select', 'sections', get_string('select_sections_order', 'local_railvieweradmin'), $sections, ['multiple' => 'multiple', 'size' => 10]);
        $mform->setType('sections', PARAM_RAW); // weil mehrere Werte als Array

        // Hin-/Rückfahrt-Verknüpfung: eine andere, bereits bestehende Strecke als Gegenrichtung
        // derselben physischen Strecke markieren. Videos sind immer richtungsgebunden (siehe
        // ausführliche Begründung im Implementierungsplan) - hier wird also nie ein Abschnitt
        // wiederverwendet, sondern zwei fertige Strecken werden als Paar verknüpft.
        $routeid = (int) ($this->_customdata['routeid'] ?? 0);
        $currentpartner = (int) ($this->_customdata['currentpairedrouteid'] ?? 0);
        $eligible = $DB->get_records_sql(
            "SELECT id, name FROM {railviewer_routes}
              WHERE id <> :selfid AND (pairedrouteid IS NULL OR id = :currentpartner)
           ORDER BY name ASC",
            ['selfid' => $routeid, 'currentpartner' => $currentpartner]
        );
        $partneroptions = [0 => get_string('no_paired_route', 'local_railvieweradmin')];
        foreach ($eligible as $r) {
            $partneroptions[$r->id] = format_string($r->name);
        }
        $mform->addElement('select', 'pairedrouteid', get_string('paired_route', 'local_railvieweradmin'), $partneroptions);
        $mform->setType('pairedrouteid', PARAM_INT);
        $mform->addHelpButton('pairedrouteid', 'paired_route', 'local_railvieweradmin');

        $directionoptions = [
            '' => get_string('choosedots'),
            'hin' => get_string('direction_hin', 'local_railvieweradmin'),
            'rueck' => get_string('direction_rueck', 'local_railvieweradmin'),
        ];
        $mform->addElement('select', 'direction', get_string('route_direction', 'local_railvieweradmin'), $directionoptions);
        $mform->setType('direction', PARAM_ALPHA);
        $mform->hideIf('direction', 'pairedrouteid', 'eq', 0);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['sections'])) {
            $errors['sections'] = get_string('select_at_least_one_section', 'local_railvieweradmin');
        }
        if (!empty($data['pairedrouteid']) && empty($data['direction'])) {
            $errors['direction'] = get_string('route_direction_required', 'local_railvieweradmin');
        }
        return $errors;
    }
}
