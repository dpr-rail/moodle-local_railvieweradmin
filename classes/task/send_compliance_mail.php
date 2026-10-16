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
