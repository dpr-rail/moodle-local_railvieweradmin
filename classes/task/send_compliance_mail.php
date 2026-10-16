<?php
namespace local_railvieweradmin\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Versendet den Soll/Ist-Abschlussbericht eines Mitarbeiters als PDF per E-Mail.
 * Adhoc-Task: bei einem Fehler wiederholt Moodle den Versand automatisch.
 *
 * @package local_railvieweradmin
 */
class send_compliance_mail extends \core\task\adhoc_task {

    public function execute() {
        $data = $this->get_custom_data();
        $userid = (int) ($data->userid ?? 0);
        if (!$userid) {
            return;
        }
        if (!\local_railvieweradmin\compliance_report::send_user_report($userid)) {
            // Kein Empfänger/Feature aus -> nichts zu tun; echter Versandfehler wird unten gemeldet.
            $to = \local_railvieweradmin\compliance_report::parse_recipients(get_config('local_railvieweradmin', 'compliance_mail_to'));
            if ($to || get_config('local_railvieweradmin', 'compliance_mail_to_user')) {
                throw new \moodle_exception('compliance_mail_failed', 'local_railvieweradmin');
            }
        }
    }
}
