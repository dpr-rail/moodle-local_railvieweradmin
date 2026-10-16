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

namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Kernlogik des Lernmethoden-Features: Anforderungen, Fortschritt je Abschnitt und Methode,
 * Ersetzungsregeln, Sollzeiten und das Zusammenführen mehrfacher Zuweisungen.
 *
 * Konventionen: methodid 0 steht für "Video" (das bisherige Ansehen des Streckenvideos, gezählt
 * über die Durchlauf-Marker in railviewer_views); alle anderen IDs verweisen auf railviewer_methods.
 *
 * @package local_railvieweradmin
 */
class learning {

    const VIDEO = 0;

    /** @return bool Ist das Feature in den Plugin-Einstellungen aktiviert? */
    public static function enabled(): bool {
        return !empty(get_config('local_railvieweradmin', 'learning_methods_enabled'));
    }

    /** Legt Standard-Methoden und -Berufserfahrungstyp an, falls die Tabellen noch leer sind. */
    public static function seed_defaults(): void {
        global $DB;
        if (!$DB->count_records('railviewer_methods')) {
            $defaults = [
                ['selfdrive', 'Selbstfahren', 0],
                ['ride', 'Mitfahren', 0],
                ['selfdrive_night', 'Selbstfahren bei Nacht', 1],
                ['ride_night', 'Mitfahren bei Nacht', 1],
            ];
            foreach ($defaults as $i => $d) {
                $DB->insert_record('railviewer_methods', (object) [
                    'shortname' => $d[0], 'name' => $d[1], 'night' => $d[2], 'sortorder' => $i + 1, 'enabled' => 1,
                ]);
            }
        }
        if (!$DB->count_records('railviewer_experience_types')) {
            $DB->insert_record('railviewer_experience_types', (object) [
                'name' => 'Standard', 'sortorder' => 1, 'enabled' => 1,
            ]);
        }
    }

    /** @return \stdClass[] Methoden (id => Datensatz), nach sortorder. */
    public static function methods(bool $onlyenabled = true): array {
        global $DB;
        return $DB->get_records('railviewer_methods', $onlyenabled ? ['enabled' => 1] : null, 'sortorder ASC, id ASC');
    }

    /** @return \stdClass[] Berufserfahrungstypen (id => Datensatz). */
    public static function experience_types(bool $onlyenabled = true): array {
        global $DB;
        return $DB->get_records('railviewer_experience_types', $onlyenabled ? ['enabled' => 1] : null, 'sortorder ASC, id ASC');
    }

    /** @return \stdClass[] Abschnitte der Strecke in Streckenreihenfolge (id => Datensatz). */
    public static function route_sections(int $routeid): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT s.id, s.name, s.duration, rs.sortorder
               FROM {railviewer_route_sections} rs
               JOIN {railviewer_sections} s ON s.id = rs.sectionid
              WHERE rs.routeid = :routeid
           ORDER BY rs.sortorder ASC, rs.id ASC", ['routeid' => $routeid]);
    }

    /**
     * Geforderte Anzahl je Methode laut Anforderungsmatrix der Strecke für einen Typ.
     *
     * @return int[] methodid (0 = Video) => Anzahl
     */
    public static function route_requirements(int $routeid, int $typeid): array {
        global $DB;
        return array_map('intval', $DB->get_records_menu('railviewer_route_reqs',
            ['routeid' => $routeid, 'experiencetypeid' => $typeid], '', 'methodid, required_count'));
    }

    /**
     * Anforderungen einer bestehenden Zuweisung (Snapshot): methodid => Anzahl. Video kommt aus
     * assignments.required_views, Zusatzmethoden aus railviewer_assignment_req.
     */
    public static function assignment_requirements(\stdClass $assignment): array {
        global $DB;
        $req = [self::VIDEO => (int) $assignment->required_views];
        $rows = $DB->get_records_menu('railviewer_assignment_req', ['assignmentid' => $assignment->id], '', 'methodid, required_count');
        foreach ($rows as $methodid => $count) {
            if ((int) $methodid !== self::VIDEO && (int) $count > 0) {
                $req[(int) $methodid] = (int) $count;
            }
        }
        return $req;
    }

    /** Soll-Minuten eines Durchlaufs für Abschnitt und Methode (mit Fallback auf die Videodauer). */
    public static function minutes(int $sectionid, int $methodid, ?\stdClass $section = null): int {
        global $DB;
        static $cache = [];
        $key = $sectionid . '_' . $methodid;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $minutes = $DB->get_field('railviewer_section_durations', 'minutes', ['sectionid' => $sectionid, 'methodid' => $methodid]);
        if ($minutes === false || (int) $minutes <= 0) {
            $seconds = $section ? (int) $section->duration : (int) $DB->get_field('railviewer_sections', 'duration', ['id' => $sectionid]);
            $minutes = $seconds > 0 ? (int) ceil($seconds / 60) : 0;
            if ($methodid !== self::VIDEO && $minutes === 0) {
                $minutes = 0;
            }
        }
        return $cache[$key] = (int) $minutes;
    }

    /** @return \stdClass[] Ersetzungsregeln der Strecke, die für diese Zuweisung wirken. */
    public static function active_substitutions(\stdClass $assignment): array {
        global $DB;
        $rules = $DB->get_records('railviewer_route_substs', ['routeid' => $assignment->routeid], 'sortorder ASC, id ASC');
        $applied = $DB->get_records_menu('railviewer_assignment_subst', ['assignmentid' => $assignment->id], '', 'substid, applied');
        $active = [];
        foreach ($rules as $rule) {
            if (empty($rule->userchoice) || !empty($applied[$rule->id])) {
                $active[] = $rule;
            }
        }
        return $active;
    }

    /**
     * Fortschritt einer Zuweisung je Abschnitt und Methode.
     *
     * @return array [
     *   'complete' => bool, 'waived' => bool,
     *   'video' => ['required','completed','complete'],
     *   'methods' => [methodid => name],
     *   'sections' => [sectionid => ['name' => , 'cells' => [methodid => [required, confirmed, pending, substituted, missing, complete]]]],
     *   'missing' => [[sectionid, sectionname, methodid, methodname, count], ...],
     * ]
     */
    public static function status(\stdClass $assignment): array {
        global $DB;

        $req = self::assignment_requirements($assignment);
        $methods = self::methods(false);
        $methodnames = [];
        foreach ($req as $methodid => $count) {
            if ($methodid !== self::VIDEO && $count > 0 && isset($methods[$methodid])) {
                $methodnames[$methodid] = format_string($methods[$methodid]->name);
            }
        }

        $video = [
            'required' => (int) $req[self::VIDEO],
            'completed' => (int) $assignment->completed_views,
        ];
        $video['complete'] = $video['completed'] >= $video['required'];

        // Bestätigte und ausstehende Fahrten je Abschnitt/Methode (einzeln, nach Datum – für die
        // Zuordnung von Ersatzfahrten im Bericht).
        $confirmed = [];
        $pending = [];
        $confirmedids = [];
        $rows = $DB->get_records_sql(
            "SELECT rs.id AS rsid, rs.sectionid, r.id AS rideid, r.methodid, r.status
               FROM {railviewer_rides} r
               JOIN {railviewer_ride_sections} rs ON rs.rideid = r.id
              WHERE r.assignmentid = :aid AND r.status IN ('confirmed', 'pending')
           ORDER BY r.ridedate, r.id", ['aid' => $assignment->id]);
        foreach ($rows as $row) {
            if ($row->status === 'confirmed') {
                $confirmed[$row->sectionid][$row->methodid] = ($confirmed[$row->sectionid][$row->methodid] ?? 0) + 1;
                $confirmedids[$row->sectionid][$row->methodid][] = (int) $row->rideid;
            } else {
                $pending[$row->sectionid][$row->methodid] = ($pending[$row->sectionid][$row->methodid] ?? 0) + 1;
            }
        }

        $rules = self::active_substitutions($assignment);
        $sections = [];
        $missinglist = [];
        $allcomplete = true;
        foreach (self::route_sections((int) $assignment->routeid) as $section) {
            $cells = [];
            $surplus = [];
            $surplusids = [];
            foreach ($methodnames as $methodid => $unused) {
                $have = $confirmed[$section->id][$methodid] ?? 0;
                $cells[$methodid] = [
                    'required' => $req[$methodid],
                    'confirmed' => $have,
                    'pending' => $pending[$section->id][$methodid] ?? 0,
                    'substituted' => 0,
                    'substs' => [],
                ];
            }
            // Überschuss auch von nicht geforderten Methoden (z.B. Tagfahrten für eine Nachtanforderung).
            foreach ($methods as $methodid => $m) {
                $required = $req[$methodid] ?? 0;
                $have = $confirmed[$section->id][$methodid] ?? 0;
                $surplus[$methodid] = max(0, $have - $required);
                // Überschussfahrten = die späteren Fahrten nach den ersten $required (nach Datum).
                $surplusids[$methodid] = array_slice($confirmedids[$section->id][$methodid] ?? [], $required);
            }
            foreach ($methodnames as $methodid => $unused) {
                $deficit = max(0, $cells[$methodid]['required'] - $cells[$methodid]['confirmed']);
                foreach ($rules as $rule) {
                    if ($deficit <= 0) {
                        break;
                    }
                    if ((int) $rule->methodid !== $methodid || (int) $rule->factor < 1) {
                        continue;
                    }
                    $by = (int) $rule->bymethodid;
                    $available = intdiv($surplus[$by] ?? 0, (int) $rule->factor);
                    $use = min($deficit, $available);
                    if ($use > 0) {
                        $deficit -= $use;
                        $surplus[$by] -= $use * (int) $rule->factor;
                        $cells[$methodid]['substituted'] += $use;
                        $consumed = array_splice($surplusids[$by], 0, $use * (int) $rule->factor);
                        $cells[$methodid]['substs'][] = [
                            'bymethodid' => $by,
                            'bymethodname' => isset($methods[$by]) ? format_string($methods[$by]->name) : '?',
                            'factor' => (int) $rule->factor,
                            'count' => $use,
                            'rideids' => $consumed,
                        ];
                    }
                }
                $cells[$methodid]['missing'] = $deficit;
                $cells[$methodid]['complete'] = $deficit === 0;
                if ($deficit > 0) {
                    $allcomplete = false;
                    $missinglist[] = [
                        'sectionid' => (int) $section->id,
                        'sectionname' => format_string($section->name),
                        'methodid' => $methodid,
                        'methodname' => $methodnames[$methodid],
                        'count' => $deficit,
                    ];
                }
            }
            $sections[$section->id] = ['name' => format_string($section->name), 'cells' => $cells];
        }

        $waived = !empty($assignment->waived);
        return [
            'waived' => $waived,
            'complete' => $waived || ($video['complete'] && $allcomplete),
            'video' => $video,
            'methods' => $methodnames,
            'sections' => $sections,
            'missing' => $missinglist,
        ];
    }

    /** Ist die Zuweisung vollständig erfüllt (feature-abhängig)? */
    public static function is_complete(\stdClass $assignment): bool {
        if (!self::enabled()) {
            return (int) $assignment->completed_views >= (int) $assignment->required_views;
        }
        return self::status($assignment)['complete'];
    }

    /**
     * Soll- und erbrachte Dauer (Minuten) einer Zuweisung, anteilig über Abschnitte.
     *
     * @return array ['soll' => int, 'ist' => int, 'incomplete' => bool, 'bysection' => [...]]
     */
    public static function duration(\stdClass $assignment, ?array $status = null): array {
        $status = $status ?? self::status($assignment);
        $req = self::assignment_requirements($assignment);
        $soll = 0;
        $ist = 0;
        $incomplete = false;
        $bysection = [];
        foreach (self::route_sections((int) $assignment->routeid) as $section) {
            $s = 0;
            $i = 0;
            // Video: streckenweite Durchläufe × Videodauer des Abschnitts.
            $vmin = self::minutes((int) $section->id, self::VIDEO, $section);
            if ($req[self::VIDEO] > 0) {
                if ($vmin === 0) {
                    $incomplete = true;
                }
                $s += $req[self::VIDEO] * $vmin;
                $i += min($status['video']['completed'], $req[self::VIDEO]) * $vmin;
            }
            foreach ($status['sections'][$section->id]['cells'] ?? [] as $methodid => $cell) {
                $mmin = self::minutes((int) $section->id, (int) $methodid, $section);
                if ($mmin === 0) {
                    $incomplete = true;
                }
                $s += $cell['required'] * $mmin;
                $i += min($cell['required'], $cell['confirmed'] + $cell['substituted']) * $mmin;
            }
            $bysection[$section->id] = ['soll' => $s, 'ist' => $i];
            $soll += $s;
            $ist += $i;
        }
        if (!empty($assignment->waived)) {
            return ['soll' => 0, 'ist' => 0, 'incomplete' => false, 'bysection' => []];
        }
        return ['soll' => $soll, 'ist' => $ist, 'incomplete' => $incomplete, 'bysection' => $bysection];
    }

    /** "42 h 30 min" / "45 min". */
    public static function format_minutes(int $minutes): string {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h . ' h' . ($m ? ' ' . $m . ' min' : '');
    }

    /**
     * Legt eine Zuweisung an oder führt sie mit einer bestehenden zusammen: je Methode gilt das
     * Maximum (nie absenken, nie addieren). Eine nicht erlassene Zuweisung hebt ein früheres
     * "erlassen" auf; erlassen wird nur eine neue Zuweisung.
     *
     * @param int|null $einsatzstelleid Herkunft (wird in railviewer_user_einsatz vermerkt)
     * @return array ['status' => created|merged|exists, 'assignmentid' => int]
     */
    public static function merge_assignment(int $userid, int $routeid, int $typeid, int $fallbackviews = 1,
            bool $waived = false, string $reason = '', int $byuserid = 0, ?int $einsatzstelleid = null): array {
        global $DB;

        $reqs = $typeid ? self::route_requirements($routeid, $typeid) : [];
        $video = array_key_exists(self::VIDEO, $reqs) ? (int) $reqs[self::VIDEO] : $fallbackviews;

        if ($einsatzstelleid) {
            if (!$DB->record_exists('railviewer_user_einsatz', ['userid' => $userid, 'einsatzstelleid' => $einsatzstelleid])) {
                $DB->insert_record('railviewer_user_einsatz', (object) [
                    'userid' => $userid, 'einsatzstelleid' => $einsatzstelleid,
                    'experiencetypeid' => $typeid ?: null, 'timecreated' => time(),
                ]);
            }
        }

        $existing = $DB->get_record('railviewer_assignments', ['userid' => $userid, 'routeid' => $routeid]);
        if (!$existing) {
            $id = $DB->insert_record('railviewer_assignments', (object) [
                'userid' => $userid, 'routeid' => $routeid, 'required_views' => $video, 'completed_views' => 0,
                'experiencetypeid' => $typeid ?: null,
                'waived' => $waived ? 1 : 0, 'waivereason' => $waived ? $reason : null,
                'waivedby' => $waived ? $byuserid : null, 'waivedtime' => $waived ? time() : null,
            ]);
            foreach ($reqs as $methodid => $count) {
                if ((int) $methodid !== self::VIDEO && $count > 0) {
                    $DB->insert_record('railviewer_assignment_req', (object) [
                        'assignmentid' => $id, 'methodid' => $methodid, 'required_count' => $count,
                    ]);
                }
            }
            return ['status' => 'created', 'assignmentid' => (int) $id];
        }

        $changed = false;
        if ($video > (int) $existing->required_views) {
            $existing->required_views = $video;
            $changed = true;
        }
        if (!$waived && !empty($existing->waived)) {
            $existing->waived = 0;
            $existing->waivereason = null;
            $existing->waivedby = null;
            $existing->waivedtime = null;
            $changed = true;
        }
        if ($changed) {
            $DB->update_record('railviewer_assignments', $existing);
        }
        $current = $DB->get_records_menu('railviewer_assignment_req', ['assignmentid' => $existing->id], '', 'methodid, required_count');
        foreach ($reqs as $methodid => $count) {
            if ((int) $methodid === self::VIDEO || $count <= 0) {
                continue;
            }
            if (!isset($current[$methodid])) {
                $DB->insert_record('railviewer_assignment_req', (object) [
                    'assignmentid' => $existing->id, 'methodid' => $methodid, 'required_count' => $count,
                ]);
                $changed = true;
            } else if ($count > (int) $current[$methodid]) {
                $DB->set_field('railviewer_assignment_req', 'required_count', $count,
                    ['assignmentid' => $existing->id, 'methodid' => $methodid]);
                $changed = true;
            }
        }
        return ['status' => $changed ? 'merged' : 'exists', 'assignmentid' => (int) $existing->id];
    }

    /**
     * Gesamtübersicht eines Nutzers über alle Zuweisungen.
     *
     * @return array ['items' => [[assignment, route, status, duration]], 'soll', 'ist', 'incomplete',
     *                'allcomplete', 'einsatzstellen' => names]
     */
    public static function user_overview(int $userid): array {
        global $DB;
        $assignments = $DB->get_records_sql(
            "SELECT ra.*, r.name AS routename, r.direction, r.pairedrouteid FROM {railviewer_assignments} ra
               JOIN {railviewer_routes} r ON r.id = ra.routeid
              WHERE ra.userid = :uid ORDER BY r.name, ra.id", ['uid' => $userid]);
        // Hin-/Rückfahrt-Paare nebeneinander (Hinfahrt zuerst), sonst nach Name.
        $assignments = array_values($assignments);
        usort($assignments, function($x, $y) {
            $kx = !empty($x->pairedrouteid) ? min((int) $x->routeid, (int) $x->pairedrouteid) : (int) $x->routeid;
            $ky = !empty($y->pairedrouteid) ? min((int) $y->routeid, (int) $y->pairedrouteid) : (int) $y->routeid;
            if ($kx !== $ky) {
                return strcasecmp((string) $x->routename, (string) $y->routename) ?: $kx <=> $ky;
            }
            return strcmp((string) ($y->direction ?? ''), (string) ($x->direction ?? '')) ?: ((int) $x->id <=> (int) $y->id);
        });
        $items = [];
        $soll = 0;
        $ist = 0;
        $incomplete = false;
        $allcomplete = !empty($assignments);
        foreach ($assignments as $a) {
            $status = self::status($a);
            $duration = self::duration($a, $status);
            $soll += $duration['soll'];
            $ist += $duration['ist'];
            $incomplete = $incomplete || $duration['incomplete'];
            $allcomplete = $allcomplete && $status['complete'];
            $items[] = ['assignment' => $a, 'status' => $status, 'duration' => $duration];
        }
        $einsatz = $DB->get_records_sql(
            "SELECT es.id, es.name FROM {railviewer_user_einsatz} ue
               JOIN {railviewer_einsatzstellen} es ON es.id = ue.einsatzstelleid
              WHERE ue.userid = :uid ORDER BY es.name", ['uid' => $userid]);
        return [
            'items' => $items, 'soll' => $soll, 'ist' => $ist, 'incomplete' => $incomplete,
            'allcomplete' => $allcomplete,
            'einsatzstellen' => array_map(function($e) { return format_string($e->name); }, $einsatz),
        ];
    }

    /**
     * Löst die Zielstrecken einer Zuweisung auf: gewählte Strecken + alle Strecken der
     * Einsatzstelle, optional jeweils mit der verknüpften Gegenrichtung; dedupliziert.
     *
     * @return int[]
     */
    public static function resolve_routes(array $routeids, int $einsatzstelleid = 0, bool $bothdirections = false): array {
        global $DB;
        $ids = array_map('intval', $routeids);
        if ($einsatzstelleid) {
            $ids = array_merge($ids, array_map('intval', $DB->get_fieldset_select(
                'railviewer_einsatz_routes', 'routeid', 'einsatzstelleid = ?', [$einsatzstelleid])));
        }
        $ids = array_values(array_unique($ids));
        if ($bothdirections) {
            foreach ($ids as $routeid) {
                $partnerid = $DB->get_field('railviewer_routes', 'pairedrouteid', ['id' => $routeid]);
                if ($partnerid) {
                    $ids[] = (int) $partnerid;
                }
            }
            $ids = array_values(array_unique($ids));
        }
        return $ids;
    }

    // ---- Abschlusserklärung ------------------------------------------------------------------

    /** @return bool Muss der Lernende nach Abschluss aller Fahrten eine Erklärung abgeben? */
    public static function declaration_required(): bool {
        return self::enabled() && !empty(get_config('local_railvieweradmin', 'declaration_enabled'));
    }

    /** Zeitpunkt der zuletzt erfüllten Zuweisung des Nutzers (0, wenn keine). */
    private static function last_completion_time(int $userid): int {
        global $DB;
        return (int) $DB->get_field_sql('SELECT MAX(completedtime) FROM {railviewer_assignments} WHERE userid = ? AND waived = 0', [$userid]);
    }

    /**
     * Stand der Abschlusserklärung: notrequired | notyet (Fahrten noch offen) | pending | declined | accepted.
     * Eine Antwort gilt nur, wenn sie nach der letzten Erfüllung einer Zuweisung abgegeben wurde;
     * kommt später eine neue Zuweisung dazu und wird erfüllt, ist erneut eine Erklärung nötig.
     */
    public static function declaration_status(int $userid, ?bool $allcomplete = null): string {
        global $DB;
        if (!self::declaration_required()) {
            return 'notrequired';
        }
        if ($allcomplete === null) {
            $allcomplete = self::user_overview($userid)['allcomplete'];
        }
        if (!$allcomplete) {
            return 'notyet';
        }
        $since = self::last_completion_time($userid);
        $latest = $DB->get_records_select('railviewer_declarations', 'userid = ? AND timecreated >= ?', [$userid, $since],
            'timecreated DESC, id DESC', '*', 0, 1);
        $latest = reset($latest);
        if (!$latest) {
            return 'pending';
        }
        return $latest->status === 'accepted' ? 'accepted' : 'declined';
    }

    /** Ist der gesamte Auftrag erledigt (alle Zuweisungen erfüllt und ggf. Erklärung bestätigt)? */
    public static function order_complete(int $userid, ?array $overview = null): bool {
        $overview = $overview ?? self::user_overview($userid);
        if (!$overview['allcomplete']) {
            return false;
        }
        $status = self::declaration_status($userid, true);
        return $status === 'notrequired' || $status === 'accepted';
    }

    /** Erklärungstext mit ersetzten Platzhaltern (Werte escaped; Text per format_text bereinigt). */
    public static function render_declaration(int $userid): string {
        global $DB;
        $text = (string) get_config('local_railvieweradmin', 'declaration_text');
        if (trim(strip_tags($text)) === '') {
            $text = get_string('declaration_text_default', 'local_railvieweradmin');
        }
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $overview = self::user_overview($userid);
        $routes = [];
        foreach ($overview['items'] as $item) {
            $routes[] = format_string($item['assignment']->routename);
        }
        $replace = [
            '{firstname}' => s($user->firstname),
            '{lastname}' => s($user->lastname),
            '{fullname}' => s(fullname($user)),
            '{idnumber}' => s($user->idnumber),
            '{routes}' => s(implode(', ', $routes)),
            '{einsatzstellen}' => s(implode(', ', $overview['einsatzstellen'])),
            '{duration}' => s(self::format_minutes($overview['soll'])),
            '{date}' => userdate(time(), get_string('strftimedatefullshort', 'langconfig')),
        ];
        return format_text(strtr($text, $replace), FORMAT_HTML, ['context' => \context_system::instance()]);
    }

    /**
     * Antwort des Lernenden auf die Erklärung speichern. Bei Bestätigung werden nachgelagerte
     * Aktionen (Event all_routes_completed → Webhook/Compliance-Mail) ausgelöst.
     */
    public static function answer_declaration(int $userid, bool $accept, string $reason = ''): void {
        global $DB;
        if (!in_array(self::declaration_status($userid), ['pending', 'declined'], true)) {
            throw new \moodle_exception('declaration_not_open', 'local_railvieweradmin');
        }
        $DB->insert_record('railviewer_declarations', (object) [
            'userid' => $userid, 'status' => $accept ? 'accepted' : 'declined',
            'declinereason' => $accept ? null : trim($reason),
            'textsnapshot' => self::render_declaration($userid), 'timecreated' => time(),
        ]);
        if ($accept) {
            \local_railvieweradmin\event\all_routes_completed::create([
                'context' => \context_system::instance(),
                'relateduserid' => $userid,
                'other' => ['routecount' => $DB->count_records('railviewer_assignments', ['userid' => $userid])],
            ])->trigger();
        }
    }

    /**
     * Anzeigename einer Strecke inkl. Richtung ("Hinfahrt: Name"), sofern sie Teil eines
     * Hin-/Rückfahrt-Paars ist. $route braucht name/routename, direction, pairedrouteid.
     */
    public static function route_label(\stdClass $route): string {
        $name = format_string($route->routename ?? $route->name ?? '');
        if (!empty($route->pairedrouteid) && !empty($route->direction)
                && get_string_manager()->string_exists('direction_' . $route->direction, 'local_railvieweradmin')) {
            return get_string('direction_' . $route->direction, 'local_railvieweradmin') . ': ' . $name;
        }
        return $name;
    }

    /**
     * Daten für die Raster-Darstellung (Spalten = Abschnitte, je Methode eine Kopfzeile mit dem Stand
     * und je Lernfahrt eine Zeile; Fahrten als zusammenhängende Balken). Gemeinsam genutzt von
     * Bericht (HTML/PDF) – gleiche Anordnung wie bei der Erfassung auf rides.php.
     *
     * @return array ['sections' => [id => name] (Reihenfolge), 'methods' => [mid => ['name', 'need', 'rows' => [[col => ride]]]]]
     */
    public static function grid_data(\stdClass $assignment, array $status): array {
        global $DB;
        $sections = array_values(self::route_sections((int) $assignment->routeid));
        $colindex = [];
        foreach ($sections as $i => $sec) {
            $colindex[(int) $sec->id] = $i;
        }
        $req = self::assignment_requirements($assignment);

        $rideranges = [];
        $rides = $DB->get_records_sql(
            "SELECT r.id, r.methodid, r.ridedate, r.trainnumber, r.status, r.isexternal, r.trainername, r.evu,
                    t.firstname, t.lastname, t.firstnamephonetic, t.lastnamephonetic, t.middlename, t.alternatename
               FROM {railviewer_rides} r
          LEFT JOIN {user} t ON t.id = r.trainerid
              WHERE r.assignmentid = :aid AND r.status IN ('confirmed','pending')
           ORDER BY r.ridedate, r.id", ['aid' => $assignment->id]);
        foreach ($rides as $r) {
            $cols = [];
            foreach ($DB->get_fieldset_select('railviewer_ride_sections', 'sectionid', 'rideid = ?', [$r->id]) as $sid) {
                if (isset($colindex[(int) $sid])) {
                    $cols[] = $colindex[(int) $sid];
                }
            }
            if ($cols) {
                $r->from = min($cols);
                $r->to = max($cols);
                $rideranges[(int) $r->methodid][] = $r;
            }
        }

        $show = [];
        foreach (array_keys($status['methods']) as $mid) {
            $show[$mid] = true;
        }
        foreach ($DB->get_records('railviewer_route_substs', ['routeid' => $assignment->routeid]) as $rule) {
            $show[(int) $rule->methodid] = true;
            $show[(int) $rule->bymethodid] = true;
        }
        foreach (array_keys($rideranges) as $mid) {
            $show[$mid] = true;
        }
        $methods = [];
        foreach (self::methods(false) as $m) {
            if (empty($show[$m->id])) {
                continue;
            }
            $rows = [];
            foreach ($rideranges[(int) $m->id] ?? [] as $r) {
                $placed = false;
                foreach ($rows as $ri => $occupied) {
                    $free = true;
                    for ($c = $r->from; $c <= $r->to; $c++) {
                        if (!empty($occupied[$c])) {
                            $free = false;
                            break;
                        }
                    }
                    if ($free) {
                        for ($c = $r->from; $c <= $r->to; $c++) {
                            $rows[$ri][$c] = $r;
                        }
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $row = [];
                    for ($c = $r->from; $c <= $r->to; $c++) {
                        $row[$c] = $r;
                    }
                    $rows[] = $row;
                }
            }
            $methods[(int) $m->id] = ['name' => format_string($m->name), 'need' => (int) ($req[(int) $m->id] ?? 0), 'rows' => $rows];
        }
        return [
            'sections' => array_map(function($sec) { return ['id' => (int) $sec->id, 'name' => format_string($sec->name)]; }, $sections),
            'methods' => $methods,
        ];
    }

    /**
     * Fahrten, die der Nutzer als Ausbilder noch bestätigen oder ablehnen soll.
     *
     * @return \stdClass[] id, ridedate, trainnumber, Fahrender (Namensfelder), methodname, routename
     */
    public static function pending_confirmations(int $userid): array {
        global $DB;
        if (!self::enabled()) {
            return [];
        }
        return $DB->get_records_sql(
            "SELECT r.id, r.ridedate, r.trainnumber, r.timecreated, m.name AS methodname, rt.name AS routename,
                    u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
               FROM {railviewer_rides} r
               JOIN {user} u ON u.id = r.userid
               JOIN {railviewer_methods} m ON m.id = r.methodid
               JOIN {railviewer_routes} rt ON rt.id = r.routeid
              WHERE r.trainerid = :me AND r.status = 'pending' AND u.deleted = 0
           ORDER BY r.timecreated, r.id", ['me' => $userid]);
    }
}
