<?php
require_once(__DIR__.'/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:deletesections', $context);
require_sesskey();

$id = required_param('id', PARAM_INT);

global $DB;

if (!$DB->record_exists('railviewer_ebulas', ['id' => $id])) {
    throw new moodle_exception('record_not_found', 'local_railvieweradmin');
}

// Löscht nur den Roh-Fahrplan selbst - bereits einem Abschnitt zugewiesene Ausschnitte
// (railviewer_ebula_section) bleiben unangetastet, die sind als fertiges HTML/JSON kopiert und
// benötigen die Roh-XML nicht mehr. Der Verweis (ebulaid) auf diesen dann gelöschten
// Fahrplan wird zu einer verwaisten ID, ohne dass das die Anzeige des Ausschnitts beeinträchtigt
// (siehe ebulas.php/index_ebulas.php - dort wird nicht gegen railviewer_ebulas zurückverknüpft).
$DB->delete_records('railviewer_ebulas', ['id' => $id]);

redirect(new moodle_url('/local/railvieweradmin/ebulas.php'),
    get_string('record_deleted', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
