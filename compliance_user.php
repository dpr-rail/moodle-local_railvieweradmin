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

// Soll/Ist-Bericht eines Mitarbeiters: Bildschirmansicht, PDF, Fahrten widerrufen, Bericht senden.
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:viewprogress', $context);

$userid = optional_param('userid', 0, PARAM_INT);
$self = new moodle_url('/local/railvieweradmin/compliance_user.php', $userid ? ['userid' => $userid] : []);
$PAGE->set_context($context);
$PAGE->set_url($self);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('compliance_user_title', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('compliance_user_title', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('compliance_user_title', 'local_railvieweradmin'));

global $DB;
if (!\local_railvieweradmin\learning::enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('learning_feature_disabled', 'local_railvieweradmin'), 'info');
    echo $OUTPUT->footer();
    exit;
}

if ($userid && optional_param('download', 0, PARAM_INT)) {
    require_sesskey();
    send_file(\local_railvieweradmin\compliance_report::render_user_pdf($userid), 'compliance_' . $userid . '_' . date('Ymd') . '.pdf',
        0, 0, true, true, 'application/pdf');
    exit;
}

if ($userid && ($action = optional_param('action', '', PARAM_ALPHA))) {
    require_sesskey();
    if ($action === 'revoke') {
        require_capability('local/railvieweradmin:assignroutes', $context);
        \local_railvieweradmin\rides::revoke(required_param('rideid', PARAM_INT));
        redirect($self, get_string('ride_revoked_msg', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'sendmail') {
        require_capability('local/railvieweradmin:assignroutes', $context);
        $ok = \local_railvieweradmin\compliance_report::send_user_report($userid);
        redirect($self, get_string($ok ? 'compliance_mail_sent_msg' : 'compliance_mail_test_failed', 'local_railvieweradmin'), null,
            $ok ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();

if (!$userid) {
    // Nutzerauswahl per Tippsuche (Admin-Endpunkt).
    echo html_writer::tag('p', get_string('compliance_user_pick', 'local_railvieweradmin'));
    echo html_writer::empty_tag('input', ['type' => 'text', 'id' => 'cu-q', 'class' => 'form-control', 'autocomplete' => 'off',
        'placeholder' => get_string('ride_trainer_placeholder', 'local_railvieweradmin'), 'style' => 'max-width:30em']);
    echo html_writer::div('', 'list-group', ['id' => 'cu-results', 'style' => 'max-width:30em']);
    $PAGE->requires->js_init_code('
        var timer = null, seq = 0, q = document.getElementById("cu-q"), box = document.getElementById("cu-results");
        q.addEventListener("input", function() {
            clearTimeout(timer); var term = q.value.trim();
            if (term.length < 2) { box.innerHTML = ""; return; }
            timer = setTimeout(function() {
                var mine = ++seq;
                fetch(M.cfg.wwwroot + "/local/railvieweradmin/ajax/search_users.php?sesskey=" + M.cfg.sesskey + "&q=" + encodeURIComponent(term))
                    .then(function(r) { return r.json(); })
                    .then(function(list) {
                        if (mine !== seq) { return; }
                        box.innerHTML = "";
                        list.forEach(function(u) {
                            var a = document.createElement("a"); a.className = "list-group-item list-group-item-action";
                            a.href = M.cfg.wwwroot + "/local/railvieweradmin/compliance_user.php?userid=" + u.id; a.textContent = u.name;
                            box.appendChild(a);
                        });
                    });
            }, 250);
        });');
    echo $OUTPUT->footer();
    exit;
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
echo html_writer::link(new moodle_url('/local/railvieweradmin/assignments.php'), get_string('back_to_admin', 'local_railvieweradmin'));
echo ' ';
echo html_writer::link(new moodle_url($self, ['download' => 1, 'sesskey' => sesskey()]),
    get_string('compliance_download_pdf', 'local_railvieweradmin'), ['class' => 'btn btn-primary']);
if (has_capability('local/railvieweradmin:assignroutes', $context)) {
    echo ' ' . html_writer::link(new moodle_url($self, ['action' => 'sendmail', 'sesskey' => sesskey()]),
        get_string('compliance_send_now', 'local_railvieweradmin'), ['class' => 'btn btn-secondary']);
}

echo html_writer::div(\local_railvieweradmin\compliance_report::user_html($userid, false), 'mt-3');

// Fahrten widerrufen (nur mit assignroutes).
if (has_capability('local/railvieweradmin:assignroutes', $context)) {
    $rides = $DB->get_records_sql(
        "SELECT r.id, r.ridedate, r.trainnumber, r.status, r.isexternal, m.name AS methodname FROM {railviewer_rides} r
           JOIN {railviewer_methods} m ON m.id = r.methodid WHERE r.userid = :u AND r.status = 'confirmed' ORDER BY r.ridedate DESC", ['u' => $userid]);
    if ($rides) {
        echo $OUTPUT->heading(get_string('rides_revoke_heading', 'local_railvieweradmin'), 3);
        $table = new html_table();
        $table->head = [get_string('ride_date', 'local_railvieweradmin'), get_string('ride_method', 'local_railvieweradmin'),
            get_string('ride_train', 'local_railvieweradmin'), get_string('actions', 'local_railvieweradmin')];
        foreach ($rides as $r) {
            $url = new moodle_url($self, ['action' => 'revoke', 'rideid' => $r->id, 'sesskey' => sesskey()]);
            $table->data[] = [userdate($r->ridedate, get_string('strftimedatefullshort', 'langconfig')), format_string($r->methodname),
                s($r->trainnumber), html_writer::link($url, get_string('ride_revoke', 'local_railvieweradmin'),
                    ['onclick' => 'return confirm(' . json_encode(get_string('confirm_delete', 'local_railvieweradmin')) . ');'])];
        }
        echo html_writer::table($table);
    }
}
echo $OUTPUT->footer();
