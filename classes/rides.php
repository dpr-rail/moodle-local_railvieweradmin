<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Erfassung und Bestätigung von Lernfahrten (Lernmethoden-Feature).
 *
 * @package local_railvieweradmin
 */
class rides {

    const MAX_SIGNATURE_BYTES = 400000;

    /**
     * Legt eine Fahrt an.
     *
     * @param \stdClass $user der Fahrende
     * @param array $in Felder: assignmentid, methodid, ridedate (Unix), trainnumber, fromsectionid, tosectionid,
     *        trainerid (intern) ODER external=true + evu, trainername, signature (PNG-DataURL)
     * @return int Fahrt-ID
     * @throws \moodle_exception bei ungültigen Eingaben
     */
    public static function create(\stdClass $user, array $in): int {
        global $DB;

        $assignment = $DB->get_record('railviewer_assignments', ['id' => (int) $in['assignmentid'], 'userid' => $user->id], '*', MUST_EXIST);
        if (!empty($assignment->waived)) {
            throw new \moodle_exception('ride_invalid', 'local_railvieweradmin');
        }
        $method = $DB->get_record('railviewer_methods', ['id' => (int) $in['methodid'], 'enabled' => 1]);
        if (!$method) {
            throw new \moodle_exception('ride_invalid', 'local_railvieweradmin');
        }

        // Abschnittsbereich in Streckenreihenfolge prüfen und expandieren.
        $sections = array_values(learning::route_sections((int) $assignment->routeid));
        $index = [];
        foreach ($sections as $i => $s) {
            $index[(int) $s->id] = $i;
        }
        $from = (int) $in['fromsectionid'];
        $to = (int) $in['tosectionid'];
        if (!isset($index[$from]) || !isset($index[$to]) || $index[$from] > $index[$to]) {
            throw new \moodle_exception('ride_invalid_range', 'local_railvieweradmin');
        }

        $trainnumber = trim((string) $in['trainnumber']);
        if ($trainnumber === '' || \core_text::strlen($trainnumber) > 30) {
            throw new \moodle_exception('ride_invalid_train', 'local_railvieweradmin');
        }
        $ridedate = (int) $in['ridedate'];
        if ($ridedate <= 0 || $ridedate > time() + DAYSECS) {
            throw new \moodle_exception('ride_invalid_date', 'local_railvieweradmin');
        }

        $external = !empty($in['external']);
        $ride = (object) [
            'assignmentid' => $assignment->id, 'userid' => $user->id, 'routeid' => $assignment->routeid,
            'methodid' => $method->id, 'ridedate' => $ridedate, 'trainnumber' => $trainnumber,
            'fromsectionid' => $from, 'tosectionid' => $to, 'timecreated' => time(),
            'isexternal' => $external ? 1 : 0, 'trainerid' => null, 'evu' => null, 'trainername' => null,
            'status' => 'pending', 'rejectreason' => null, 'timeresponded' => null,
        ];

        $png = null;
        if ($external) {
            $ride->evu = trim((string) ($in['evu'] ?? ''));
            $ride->trainername = trim((string) ($in['trainername'] ?? ''));
            if ($ride->evu === '' || $ride->trainername === '') {
                throw new \moodle_exception('ride_external_required', 'local_railvieweradmin');
            }
            $png = self::decode_signature((string) ($in['signature'] ?? ''));
            $ride->status = 'confirmed';
            $ride->timeresponded = time();
        } else {
            $trainerid = (int) ($in['trainerid'] ?? 0);
            if (!$trainerid || $trainerid === (int) $user->id
                    || !$DB->record_exists('user', ['id' => $trainerid, 'deleted' => 0, 'suspended' => 0])) {
                throw new \moodle_exception('ride_invalid_trainer', 'local_railvieweradmin');
            }
            $ride->trainerid = $trainerid;
        }

        $transaction = $DB->start_delegated_transaction();
        $rideid = $DB->insert_record('railviewer_rides', $ride);
        for ($i = $index[$from]; $i <= $index[$to]; $i++) {
            $DB->insert_record('railviewer_ride_sections', (object) ['rideid' => $rideid, 'sectionid' => $sections[$i]->id]);
        }
        if ($png !== null) {
            get_file_storage()->create_file_from_string([
                'contextid' => \context_system::instance()->id, 'component' => 'local_railvieweradmin',
                'filearea' => 'ridesignature', 'itemid' => $rideid, 'filepath' => '/', 'filename' => 'signature.png',
            ], $png);
        }
        $transaction->allow_commit();

        if ($external) {
            local_railvieweradmin_sync_assignment_progress($assignment->id);
        } else {
            self::notify_trainer($rideid);
        }
        return (int) $rideid;
    }

    /** PNG-DataURL prüfen und in Binärdaten wandeln. */
    private static function decode_signature(string $dataurl): string {
        if (strpos($dataurl, 'data:image/png;base64,') !== 0) {
            throw new \moodle_exception('ride_signature_required', 'local_railvieweradmin');
        }
        $bin = base64_decode(substr($dataurl, strlen('data:image/png;base64,')), true);
        if ($bin === false || strlen($bin) < 200 || strlen($bin) > self::MAX_SIGNATURE_BYTES) {
            throw new \moodle_exception('ride_signature_required', 'local_railvieweradmin');
        }
        $info = @getimagesizefromstring($bin);
        if (!$info || $info['mime'] !== 'image/png') {
            throw new \moodle_exception('ride_signature_required', 'local_railvieweradmin');
        }
        return $bin;
    }

    /** Benachrichtigt den internen Ausbilder über eine zu bestätigende Fahrt. */
    private static function notify_trainer(int $rideid): void {
        global $DB;
        $ride = $DB->get_record('railviewer_rides', ['id' => $rideid], '*', MUST_EXIST);
        $rider = $DB->get_record('user', ['id' => $ride->userid], '*', MUST_EXIST);
        $trainer = $DB->get_record('user', ['id' => $ride->trainerid], '*', MUST_EXIST);
        $url = new \moodle_url('/mod/railviewer/ride_confirm.php', ['id' => $rideid]);
        $a = (object) [
            'rider' => fullname($rider), 'date' => userdate($ride->ridedate, get_string('strftimedatefullshort', 'langconfig')),
            'train' => $ride->trainnumber,
            'method' => format_string($DB->get_field('railviewer_methods', 'name', ['id' => $ride->methodid])),
            'route' => format_string($DB->get_field('railviewer_routes', 'name', ['id' => $ride->routeid])),
            'url' => $url->out(false),
        ];
        $message = new \core\message\message();
        $message->component = 'local_railvieweradmin';
        $message->name = 'ridestoconfirm';
        $message->userfrom = $rider;
        $message->userto = $trainer;
        $message->subject = get_string('ride_message_subject', 'local_railvieweradmin', $a);
        $message->fullmessage = get_string('ride_message_body', 'local_railvieweradmin', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($message->fullmessage));
        $message->smallmessage = $message->subject;
        $message->notification = 1;
        $message->contexturl = $url->out(false);
        $message->contexturlname = get_string('ride_confirm_title', 'local_railvieweradmin');
        message_send($message);
    }

    /**
     * Ausbilder bestätigt oder lehnt ab.
     *
     * @throws \moodle_exception wenn der Nutzer nicht der Ausbilder ist oder die Fahrt nicht mehr offen ist
     */
    public static function respond(int $rideid, int $trainerid, bool $accept, string $reason = ''): void {
        global $DB;
        $ride = $DB->get_record('railviewer_rides', ['id' => $rideid], '*', MUST_EXIST);
        if ((int) $ride->trainerid !== $trainerid || $ride->status !== 'pending') {
            throw new \moodle_exception('ride_cannot_respond', 'local_railvieweradmin');
        }
        $ride->status = $accept ? 'confirmed' : 'rejected';
        $ride->rejectreason = $accept ? null : trim($reason);
        $ride->timeresponded = time();
        $DB->update_record('railviewer_rides', $ride);
        local_railvieweradmin_sync_assignment_progress($ride->assignmentid);

        // Rückmeldung an den Fahrenden.
        $rider = $DB->get_record('user', ['id' => $ride->userid]);
        $trainer = $DB->get_record('user', ['id' => $trainerid]);
        if ($rider && $trainer) {
            $a = (object) ['trainer' => fullname($trainer), 'train' => $ride->trainnumber,
                'date' => userdate($ride->ridedate, get_string('strftimedatefullshort', 'langconfig')),
                'reason' => $ride->rejectreason ?? ''];
            $message = new \core\message\message();
            $message->component = 'local_railvieweradmin';
            $message->name = 'ridedecision';
            $message->userfrom = $trainer;
            $message->userto = $rider;
            $message->subject = get_string($accept ? 'ride_decision_confirmed' : 'ride_decision_rejected', 'local_railvieweradmin', $a);
            $message->fullmessage = $message->subject . ($a->reason !== '' ? "\n" . $a->reason : '');
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = nl2br(s($message->fullmessage));
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = (new \moodle_url('/mod/railviewer/rides.php'))->out(false);
            $message->contexturlname = get_string('my_rides', 'local_railvieweradmin');
            message_send($message);
        }
    }

    /** Admin widerruft eine Fahrt (zählt danach nicht mehr). */
    public static function revoke(int $rideid): void {
        global $DB;
        $ride = $DB->get_record('railviewer_rides', ['id' => $rideid], '*', MUST_EXIST);
        if ($ride->status === 'revoked') {
            return;
        }
        $DB->set_field('railviewer_rides', 'status', 'revoked', ['id' => $rideid]);
        local_railvieweradmin_sync_assignment_progress($ride->assignmentid);
    }

    /** Aktive Nutzer für die Ausbilder-Tippsuche (ohne den Fragenden). */
    public static function search_users(string $query, int $excludeid, int $limit = 20): array {
        global $DB, $CFG;
        $query = trim($query);
        if (\core_text::strlen($query) < 2) {
            return [];
        }
        $like1 = $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':q1', false, false);
        $like2 = $DB->sql_like($DB->sql_concat('u.lastname', "' '", 'u.firstname'), ':q2', false, false);
        $like3 = $DB->sql_like('u.username', ':q3', false, false);
        $esc = $DB->sql_like_escape($query);
        $users = $DB->get_records_sql(
            "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
               FROM {user} u
              WHERE u.deleted = 0 AND u.suspended = 0 AND u.id <> :me AND u.id <> :guest
                AND ($like1 OR $like2 OR $like3)
           ORDER BY u.lastname, u.firstname",
            ['me' => $excludeid, 'guest' => $CFG->siteguest, 'q1' => "%$esc%", 'q2' => "%$esc%", 'q3' => "$esc%"],
            0, $limit);
        $result = [];
        foreach ($users as $u) {
            $result[] = ['id' => (int) $u->id, 'name' => fullname($u)];
        }
        return $result;
    }
}
