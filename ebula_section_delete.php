<?php
require_once(__DIR__.'/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:deletesections', $context);
require_sesskey();

$id = required_param('id', PARAM_INT);

global $DB;

// prüfen ob Eintrag existiert
if ($DB->record_exists('railviewer_ebula_section', ['id' => $id])) {
    $sectionid = $DB->get_record('railviewer_ebula_section', ['id' => $id])->sectionid;
    $DB->delete_records('railviewer_ebula_section', ['id' => $id]);
    redirect(new moodle_url('/local/railvieweradmin/index_ebulas.php', ['id' => $sectionid]),
        get_string('record_deleted', 'local_railvieweradmin'), 2, \core\output\notification::NOTIFY_SUCCESS);
} else {
    throw new moodle_exception('record_not_found', 'local_railvieweradmin');
}

