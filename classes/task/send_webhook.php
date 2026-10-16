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
 * Sendet einen Fortschritts-Webhook (HTTP POST, JSON) an die in den Einstellungen hinterlegte URL.
 * Als Adhoc-Task, damit ein nicht erreichbares Zielsystem die Wiedergabe des Nutzers nie
 * ausbremst; bei Fehlern wiederholt Moodle den Task automatisch mit wachsendem Abstand.
 *
 * Authentizität: Ist ein Webhook-Secret gesetzt, steht im Header X-Railviewer-Signature
 * "sha256=" + HMAC-SHA256(Body, Secret).
 *
 * @package local_railvieweradmin
 */
class send_webhook extends \core\task\adhoc_task {

    public function execute() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = trim((string) get_config('local_railvieweradmin', 'webhook_url'));
        if ($url === '') {
            return;
        }
        $body = json_encode($this->get_custom_data());
        $headers = ['Content-Type: application/json'];
        $secret = (string) get_config('local_railvieweradmin', 'webhook_secret');
        if ($secret !== '') {
            $headers[] = 'X-Railviewer-Signature: sha256=' . hash_hmac('sha256', $body, $secret);
        }

        $curl = new \curl();
        $curl->setHeader($headers);
        $curl->post($url, $body, ['CURLOPT_TIMEOUT' => 15]);
        $code = (int) ($curl->get_info()['http_code'] ?? 0);
        if ($code < 200 || $code >= 300) {
            // Exception -> Moodle plant den Task erneut ein.
            throw new \moodle_exception('webhook_failed', 'local_railvieweradmin', '', $code ?: $curl->error);
        }
    }
}
