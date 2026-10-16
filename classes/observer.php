<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Event-Observer: stellt für jedes Fortschritts-Event einen Webhook-Aufruf in die Queue.
 *
 * @package local_railvieweradmin
 */
class observer {

    public static function progress_event(\core\event\base $event) {
        global $DB;

        // Soll/Ist-Abschlussbericht per Mail, sobald der gesamte Auftrag erledigt ist.
        if ($event instanceof \local_railvieweradmin\event\all_routes_completed
                && \local_railvieweradmin\learning::enabled()
                && !empty(get_config('local_railvieweradmin', 'compliance_mail_enabled'))
                && !$DB->record_exists('railviewer_compliance_mail', ['userid' => $event->relateduserid])) {
            $mailtask = new \local_railvieweradmin\task\send_compliance_mail();
            $mailtask->set_custom_data(['userid' => (int) $event->relateduserid]);
            \core\task\manager::queue_adhoc_task($mailtask);
        }

        $url = trim((string) get_config('local_railvieweradmin', 'webhook_url'));
        if ($url === '') {
            return;
        }

        $user = $DB->get_record('user', ['id' => $event->relateduserid], 'id, username, idnumber');
        $payload = [
            'event' => $event->eventname,
            'timestamp' => $event->timecreated,
            'user' => $user ? [
                'id' => (int) $user->id,
                'username' => $user->username,
                'idnumber' => $user->idnumber,
            ] : ['id' => (int) $event->relateduserid],
            'data' => $event->other,
        ];
        if ($event->objectid) {
            $payload['assignmentid'] = (int) $event->objectid;
        }

        $task = new \local_railvieweradmin\task\send_webhook();
        $task->set_custom_data($payload);
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Beim Löschen eines Nutzers dessen Lernfahrten, Unterschriften, Erklärungen und
     * Zuweisungs-Nebendaten entfernen; bei ihm offene Bestätigungen anderer werden abgelehnt.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;
        $userid = (int) $event->objectid;
        $fs = get_file_storage();
        $contextid = \context_system::instance()->id;
        foreach ($DB->get_fieldset_select('railviewer_rides', 'id', 'userid = ?', [$userid]) as $rideid) {
            $fs->delete_area_files($contextid, 'local_railvieweradmin', 'ridesignature', $rideid);
            $DB->delete_records('railviewer_ride_sections', ['rideid' => $rideid]);
        }
        $DB->delete_records('railviewer_rides', ['userid' => $userid]);
        $DB->set_field_select('railviewer_rides', 'status', 'rejected', "trainerid = ? AND status = 'pending'", [$userid]);
        foreach ($DB->get_fieldset_select('railviewer_assignments', 'id', 'userid = ?', [$userid]) as $aid) {
            $DB->delete_records('railviewer_assignment_req', ['assignmentid' => $aid]);
            $DB->delete_records('railviewer_assignment_subst', ['assignmentid' => $aid]);
        }
        $DB->delete_records('railviewer_declarations', ['userid' => $userid]);
        $DB->delete_records('railviewer_user_einsatz', ['userid' => $userid]);
        $DB->delete_records('railviewer_compliance_mail', ['userid' => $userid]);
    }
}
