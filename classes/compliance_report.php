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
 * Compliance-Berichte als PDF (Soll-Matrix und Soll/Ist je Mitarbeiter) und Mailversand.
 *
 * @package local_railvieweradmin
 */
class compliance_report {

    /** @return string[] gültige, eindeutige E-Mail-Adressen aus "a@x.de, b@y.de; c@z.de". */
    public static function parse_recipients($raw): array {
        $out = [];
        foreach (preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $addr) {
            if (validate_email($addr)) {
                $out[strtolower($addr)] = $addr;
            }
        }
        return array_values($out);
    }

    /**
     * Sendet eine Mail (optional mit PDF-Anhang) an externe Adressen.
     *
     * @param string[] $to
     */
    public static function send_mail(array $to, string $subject, string $body, ?string $pdfdata = null, string $pdfname = 'report.pdf'): bool {
        $from = \core_user::get_noreply_user();
        $attachment = '';
        $tmp = null;
        if ($pdfdata !== null) {
            $dir = make_temp_directory('local_railvieweradmin');
            $tmp = $dir . '/' . sha1(uniqid('', true)) . '.pdf';
            file_put_contents($tmp, $pdfdata);
            $attachment = $tmp;
        }
        $ok = true;
        foreach ($to as $addr) {
            $recipient = \core_user::get_user_by_email($addr) ?: (object) [
                'id' => -99, 'email' => $addr, 'firstname' => '', 'lastname' => '', 'username' => 'railviewer-compliance',
                'mailformat' => 1, 'maildisplay' => 1, 'auth' => 'manual', 'deleted' => 0, 'suspended' => 0,
                'emailstop' => 0, 'lang' => 'de', 'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '',
                'alternatename' => '',
            ];
            $ok = email_to_user($recipient, $from, $subject, $body, nl2br(s($body)), $attachment ? $attachment : '',
                $attachment ? $pdfname : '') && $ok;
        }
        if ($tmp && file_exists($tmp)) {
            @unlink($tmp);
        }
        return $ok;
    }

    /** Erzeugt den Abschlussbericht und sendet ihn (Adressen aus den Settings und/oder an den Nutzer). */
    public static function send_user_report(int $userid): bool {
        global $DB;
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user || !learning::enabled() || empty(get_config('local_railvieweradmin', 'compliance_mail_enabled'))) {
            return false;
        }
        $to = self::parse_recipients(get_config('local_railvieweradmin', 'compliance_mail_to'));
        if (!empty(get_config('local_railvieweradmin', 'compliance_mail_to_user')) && validate_email($user->email)) {
            $to[] = $user->email;
        }
        $to = array_values(array_unique($to));
        if (!$to) {
            return false;
        }
        $a = (object) ['name' => fullname($user), 'date' => userdate(time(), get_string('strftimedatefullshort', 'langconfig'))];
        $ok = self::send_mail($to, get_string('compliance_mail_subject', 'local_railvieweradmin', $a),
            get_string('compliance_mail_body', 'local_railvieweradmin', $a), self::render_user_pdf($userid),
            'compliance_' . $userid . '_' . date('Ymd') . '.pdf');
        if ($ok && !$DB->record_exists('railviewer_compliance_mail', ['userid' => $userid])) {
            $DB->insert_record('railviewer_compliance_mail', (object) ['userid' => $userid, 'timesent' => time()]);
        } else if ($ok) {
            $DB->set_field('railviewer_compliance_mail', 'timesent', time(), ['userid' => $userid]);
        }
        return $ok;
    }

    private static function new_pdf(string $title, string $orientation = 'L', string $subtitle = ''): \local_railvieweradmin\report_pdf {
        $pdf = new \local_railvieweradmin\report_pdf($orientation, 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Railviewer');
        $pdf->SetTitle($title);
        $pdf->reporttitle = $title;
        $pdf->reportsubtitle = $subtitle;
        $pdf->footertext = get_string('compliance_generated', 'local_railvieweradmin',
            userdate(time(), get_string('strftimedatetimeshort', 'langconfig')));
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(6);
        $pdf->SetFooterMargin(14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->SetFont('freesans', '', 8);
        return $pdf;
    }

    // ---- A) Soll-Matrix ----------------------------------------------------------------------

    /**
     * @param int[] $typeids leer = alle Typen
     * @param int[] $einsatzids leer = alle Einsatzstellen
     * @return string PDF-Binärdaten
     */
    public static function render_requirements_pdf(array $typeids = [], array $einsatzids = []): string {
        $pdf = self::new_pdf(get_string('compliance_requirements_title', 'local_railvieweradmin'), 'L');
        $types = learning::experience_types();
        if ($typeids) {
            $types = array_intersect_key($types, array_flip($typeids));
        }
        foreach ($types as $type) {
            $pdf->AddPage();
            $pdf->writeHTML(self::requirements_html($type, $einsatzids, true), true, false, true, false, '');
        }
        if (!$types) {
            $pdf->AddPage();
        }
        return $pdf->Output('', 'S');
    }

    /** HTML-Tabelle für einen Berufserfahrungstyp (auch für die Bildschirmvorschau). */
    public static function requirements_html(\stdClass $type, array $einsatzids = [], bool $forpdf = false): string {
        global $DB;
        $einsatz = $DB->get_records('railviewer_einsatzstellen', null, 'name ASC');
        if ($einsatzids) {
            $einsatz = array_intersect_key($einsatz, array_flip($einsatzids));
        }
        $memberships = [];
        foreach ($DB->get_records('railviewer_einsatz_routes') as $m) {
            $memberships[$m->routeid][$m->einsatzstelleid] = true;
        }
        $methods = [0 => get_string('method_video', 'local_railvieweradmin')];
        foreach (learning::methods() as $m) {
            $methods[$m->id] = format_string($m->name);
        }
        $routes = $DB->get_records('railviewer_routes', null, 'name ASC');
        if ($einsatzids) {
            $routes = array_filter($routes, function($r) use ($memberships, $einsatz) {
                return !empty(array_intersect_key($memberships[$r->id] ?? [], $einsatz));
            });
        }

        $font = count($einsatz) + count($methods) > 14 ? 6 : 7;
        // Der Berichtstitel steht in der PDF-Kopfzeile; hier nur der Berufserfahrungstyp.
        $html = '<h2>' . s(get_string('experience_type', 'local_railvieweradmin')) . ': '
            . s(format_string($type->name)) . '</h2>';
        if (!$forpdf) {
            $html .= '<p style="font-size:7pt">' . s(get_string('compliance_generated', 'local_railvieweradmin',
                userdate(time(), get_string('strftimedatetimeshort', 'langconfig')))) . '</p>';
        }
        $html .= '<table border="1" cellpadding="2" style="font-size:' . $font . 'pt"><thead><tr style="background-color:#e8e8e8;font-weight:bold">';
        $html .= '<th width="24%">' . s(get_string('route', 'local_railvieweradmin')) . '</th>';
        $rest = 76;
        $colcount = max(1, count($einsatz) + count($methods) + 1);
        $w = round($rest / $colcount, 2);
        foreach ($einsatz as $e) {
            $html .= '<th width="' . $w . '%" align="center">' . s(format_string($e->name)) . '</th>';
        }
        foreach ($methods as $label) {
            $html .= '<th width="' . $w . '%" align="center">' . s($label) . '</th>';
        }
        $html .= '<th width="' . $w . '%" align="center">' . s(get_string('compliance_total_duration', 'local_railvieweradmin')) . '</th>';
        $html .= '</tr></thead><tbody>';

        $footnotes = [];
        foreach ($routes as $route) {
            $sections = learning::route_sections((int) $route->id);
            $req = learning::route_requirements((int) $route->id, (int) $type->id);
            $names = array_map(function($s) { return s(format_string($s->name)); }, $sections);
            $html .= '<tr nobr="true"><td width="24%"><b>' . s(learning::route_label($route)) . '</b>';
            if ($names) {
                $html .= '<br/><span style="font-size:' . ($font - 1) . 'pt;color:#555555">' . implode('<br/>', $names) . '</span>';
            }
            $html .= '</td>';
            foreach ($einsatz as $e) {
                $html .= '<td width="' . $w . '%" align="center">' . (!empty($memberships[$route->id][$e->id]) ? 'x' : '') . '</td>';
            }
            $allmethods = learning::methods(false);
            $rules = $DB->get_records('railviewer_route_substs', ['routeid' => $route->id], 'sortorder ASC, id ASC');
            $rulesby = [];
            foreach ($rules as $rule) {
                if (isset($allmethods[$rule->methodid], $allmethods[$rule->bymethodid])) {
                    $rulesby[(int) $rule->methodid][] = (int) $rule->factor . '× ' . format_string($allmethods[$rule->bymethodid]->name)
                        . ' (' . get_string($rule->userchoice ? 'compliance_subst_user' : 'compliance_subst_auto', 'local_railvieweradmin') . ')';
                    $footnotes[] = format_string($route->name) . ': 1× ' . format_string($allmethods[$rule->methodid]->name)
                        . ' = ' . (int) $rule->factor . '× ' . format_string($allmethods[$rule->bymethodid]->name)
                        . ' (' . get_string($rule->userchoice ? 'compliance_subst_user' : 'compliance_subst_auto', 'local_railvieweradmin') . ')';
                }
            }
            $total = 0;
            foreach ($methods as $mid => $label) {
                $count = (int) ($req[$mid] ?? 0);
                $runminutes = 0;
                foreach ($sections as $s) {
                    $runminutes += learning::minutes((int) $s->id, (int) $mid, $s);
                }
                $total += $count * $runminutes;
                $cell = $count > 0 ? $count . '× · ' . $runminutes . ' min' : '–';
                $cellhtml = s($cell);
                if (!empty($rulesby[$mid])) {
                    $cellhtml .= '<br/><span style="font-size:' . ($font - 2) . 'pt;color:#555555">'
                        . s(get_string('compliance_replaceable_by', 'local_railvieweradmin')) . ' '
                        . implode('<br/>', array_map('s', $rulesby[$mid])) . '</span>';
                }
                $html .= '<td width="' . $w . '%" align="center">' . $cellhtml . '</td>';
            }
            $html .= '<td width="' . $w . '%" align="center">' . s(learning::format_minutes($total)) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        if ($footnotes) {
            $html .= '<p style="font-size:7pt"><b>' . s(get_string('substitution_rules', 'local_railvieweradmin')) . ':</b><br/>'
                . implode('<br/>', array_map('s', $footnotes)) . '</p>';
        }
        return $html;
    }

    // ---- B) Soll/Ist je Mitarbeiter ----------------------------------------------------------

    public static function render_user_pdf(int $userid): string {
        $user = $GLOBALS['DB']->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $overview = learning::user_overview($userid);
        $subtitle = fullname($user) . ($user->idnumber !== '' ? ' · ' . get_string('compliance_idnumber', 'local_railvieweradmin') . ' ' . $user->idnumber : '')
            . ($overview['einsatzstellen'] ? ' · ' . get_string('einsatzstellen', 'local_railvieweradmin') . ': ' . implode(', ', $overview['einsatzstellen']) : '');
        $pdf = self::new_pdf(get_string('compliance_user_title', 'local_railvieweradmin'), 'P', $subtitle);
        $pdf->AddPage();
        $pdf->writeHTML(self::user_html($userid, true), true, false, true, false, '');
        return $pdf->Output('', 'S');
    }

    /**
     * Raster-Darstellung einer Zuweisung wie bei der Erfassung: Spalten = Abschnitte, je Lernmethode eine
     * Kopfzeile mit Soll | Ist je Abschnitt und darunter eine Zeile pro Lernfahrt (Balken über die
     * befahrenen Abschnitte mit Datum und Zugnummer; "⇄" markiert als Ersatz gewertete Fahrten).
     */
    public static function grid_html(\stdClass $assignment, array $status): string {
        $grid = learning::grid_data($assignment, $status);
        $n = count($grid['sections']);
        if (!$n || !$grid['methods']) {
            return '';
        }
        // Als Ersatz gewertete Fahrten (rideid => true).
        $substituted = [];
        foreach ($status['sections'] as $sec) {
            foreach ($sec['cells'] as $cell) {
                foreach ($cell['substs'] as $sub) {
                    foreach ($sub['rideids'] as $rid) {
                        $substituted[$rid] = true;
                    }
                }
            }
        }
        $firstw = 18;
        $w = round((100 - $firstw) / $n, 2);
        $font = $n > 8 ? 5.5 : ($n > 5 ? 6.5 : 7.5);
        $html = '<table border="1" cellpadding="2" style="font-size:' . $font . 'pt"><thead><tr style="background-color:#e8e8e8;font-weight:bold">'
            . '<th width="' . $firstw . '%">' . s(get_string('ride_method', 'local_railvieweradmin')) . '</th>';
        foreach ($grid['sections'] as $sec) {
            $html .= '<th width="' . $w . '%" align="center">' . s($sec['name']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($grid['methods'] as $mid => $m) {
            $html .= '<tr nobr="true" style="background-color:#eef2fb;font-weight:bold"><td width="' . $firstw . '%">' . s($m['name'])
                . ($m['need'] ? '<br/><span style="font-weight:normal">' . s(get_string('grid_required_n', 'local_railvieweradmin', $m['need'])) . '</span>' : '')
                . '</td>';
            foreach ($grid['sections'] as $sec) {
                $c = $status['sections'][$sec['id']]['cells'][$mid] ?? null;
                if ($c) {
                    $txt = get_string('compliance_cell', 'local_railvieweradmin', (object) ['soll' => $c['required'], 'ist' => $c['confirmed']]);
                    if ($c['substituted']) {
                        $txt .= ' + ' . get_string('compliance_cell_subst', 'local_railvieweradmin', $c['substituted']);
                    }
                    if ($c['pending']) {
                        $txt .= ' (' . get_string('compliance_cell_pending', 'local_railvieweradmin', $c['pending']) . ')';
                    }
                    $html .= '<td width="' . $w . '%" align="center" style="background-color:' . ($c['complete'] ? '#d4edda' : '#f8d7da')
                        . '">' . s($txt) . '</td>';
                } else {
                    $html .= '<td width="' . $w . '%" align="center">–</td>';
                }
            }
            $html .= '</tr>';
            $rowcount = max($m['need'], count($m['rows']));
            for ($ri = 0; $ri < $rowcount; $ri++) {
                $row = $m['rows'][$ri] ?? [];
                $label = $ri < $m['need'] ? get_string('grid_ride_n', 'local_railvieweradmin', $ri + 1) : get_string('grid_ride_extra', 'local_railvieweradmin');
                $html .= '<tr nobr="true"><td width="' . $firstw . '%">' . s($label) . '</td>';
                $c = 0;
                while ($c < $n) {
                    if (!empty($row[$c])) {
                        $r = $row[$c];
                        $span = $r->to - $r->from + 1;
                        $bg = $r->status === 'confirmed' ? '#bfe5cf' : '#ffe7a8';
                        $txt = userdate($r->ridedate, '%d.%m.%y') . ' · ' . $r->trainnumber . (!empty($substituted[$r->id]) ? ' ⇄' : '');
                        $who = !empty($r->isexternal) ? $r->trainername : fullname($r);
                        $html .= '<td colspan="' . $span . '" align="center" style="background-color:' . $bg . '">' . s($txt)
                            . '<br/><span style="font-size:' . ($font - 1) . 'pt">' . s($who) . '</span></td>';
                        $c += $span;
                    } else {
                        $html .= '<td width="' . $w . '%"></td>';
                        $c++;
                    }
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';
        $html .= '<p style="font-size:6.5pt;color:#555555">' . s(get_string('compliance_grid_legend', 'local_railvieweradmin')) . '</p>';
        return $html;
    }

    /** HTML des Mitarbeiterberichts. $forpdf bettet Unterschriften als Bilddaten ein. */
    public static function user_html(int $userid, bool $forpdf = false): string {
        global $DB;
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $overview = learning::user_overview($userid);
        // Im PDF steht der Titel schon in der Kopfzeile jeder Seite.
        $html = $forpdf ? '' : '<h2>' . s(get_string('compliance_user_title', 'local_railvieweradmin')) . '</h2>';
        // Name, ID-Nr., Einsatzstellen und Erstelldatum stehen im PDF bereits in Kopf- und Fußzeile.
        if (!$forpdf) {
            $html .= '<p><b>' . s(fullname($user)) . '</b>' . ($user->idnumber !== '' ? ' (' . s($user->idnumber) . ')' : '')
                . '<br/>' . s(get_string('compliance_generated', 'local_railvieweradmin',
                    userdate(time(), get_string('strftimedatetimeshort', 'langconfig')))) . '</p>';
            if ($overview['einsatzstellen']) {
                $html .= '<p>' . s(get_string('my_einsatzstellen', 'local_railvieweradmin')) . ': '
                    . s(implode(', ', $overview['einsatzstellen'])) . '</p>';
            }
        }
        $percent = $overview['soll'] > 0 ? (int) round(100 * $overview['ist'] / $overview['soll']) : 0;
        $html .= '<p><b>' . s(get_string('duration_summary', 'local_railvieweradmin', (object) [
            'soll' => learning::format_minutes($overview['soll']), 'ist' => learning::format_minutes($overview['ist']),
            'rest' => learning::format_minutes(max(0, $overview['soll'] - $overview['ist'])), 'percent' => $percent])) . '</b>';
        if ($overview['incomplete']) {
            $html .= ' ' . s(get_string('duration_incomplete', 'local_railvieweradmin'));
        }
        $html .= '</p>';

        $methodsall = learning::methods(false);
        foreach ($overview['items'] as $item) {
            $a = $item['assignment'];
            $st = $item['status'];
            $dur = $item['duration'];
            $typename = '';
            if (!empty($a->experiencetypeid)) {
                $typename = (string) $DB->get_field('railviewer_experience_types', 'name', ['id' => $a->experiencetypeid]);
            }
            $html .= '<h3>' . s(learning::route_label($a)) . '</h3><p style="font-size:8pt">'
                . ($typename !== '' ? s(get_string('experience_type', 'local_railvieweradmin')) . ': ' . s(format_string($typename)) . ' · ' : '')
                . s(get_string('status', 'local_railvieweradmin')) . ': '
                . s($a->waived ? get_string('waived_label', 'local_railvieweradmin') . ' (' . $a->waivereason . ')'
                    : get_string($st['complete'] ? 'status_done' : 'status_open', 'local_railvieweradmin'))
                . ' · ' . s(learning::format_minutes($dur['ist'])) . ' / ' . s(learning::format_minutes($dur['soll'])) . '</p>';
            if ($a->waived) {
                continue;
            }
            $html .= self::grid_html($a, $st);
            if ($st['video']['required'] > 0) {
                $html .= '<p style="font-size:7pt">' . s(get_string('video_progress', 'local_railvieweradmin', $st['video'])) . '</p>';
            }

            // ---- Ersetzungen: verfügbare Regeln und tatsächlich angerechnete Ersatzfahrten
            $allm = learning::methods(false);
            $rules = $DB->get_records('railviewer_route_substs', ['routeid' => $a->routeid], 'sortorder ASC, id ASC');
            $applied = $DB->get_records_menu('railviewer_assignment_subst', ['assignmentid' => $a->id], '', 'substid, applied');
            $rulelines = [];
            foreach ($rules as $rule) {
                if (!isset($allm[$rule->methodid], $allm[$rule->bymethodid])) {
                    continue;
                }
                $mode = empty($rule->userchoice) ? 'compliance_subst_auto'
                    : (!empty($applied[$rule->id]) ? 'compliance_subst_applied' : 'compliance_subst_notapplied');
                $rulelines[] = '1× ' . format_string($allm[$rule->methodid]->name) . ' = ' . (int) $rule->factor . '× '
                    . format_string($allm[$rule->bymethodid]->name) . ' – ' . get_string($mode, 'local_railvieweradmin');
            }
            $substrows = '';
            $substnotes = [];
            foreach ($st['sections'] as $sec) {
                foreach ($sec['cells'] as $mid => $c) {
                    foreach ($c['substs'] as $sub) {
                        $labels = [];
                        $ridesinfo = $DB->get_records_list('railviewer_rides', 'id', $sub['rideids'] ?: [0], 'ridedate, id', 'id, ridedate, trainnumber');
                        foreach ($ridesinfo as $ri) {
                            $labels[] = userdate($ri->ridedate, get_string('strftimedatefullshort', 'langconfig')) . ' / ' . $ri->trainnumber;
                            $substnotes[$ri->id][] = get_string('compliance_subst_note', 'local_railvieweradmin', (object) [
                                'method' => $st['methods'][$mid], 'section' => $sec['name']]);
                        }
                        $substrows .= '<tr nobr="true"><td>' . s($sec['name']) . '</td><td>' . s($st['methods'][$mid]) . '</td>'
                            . '<td align="center">' . (int) $sub['count'] . '×</td>'
                            . '<td>' . s($sub['factor'] . '× ' . $sub['bymethodname']) . '</td>'
                            . '<td>' . s(implode('; ', $labels)) . '</td></tr>';
                    }
                }
            }
            if ($rulelines || $substrows) {
                $html .= '<p style="font-size:8pt"><b>' . s(get_string('substitution_rules', 'local_railvieweradmin')) . ':</b><br/>'
                    . ($rulelines ? implode('<br/>', array_map('s', $rulelines)) : s(get_string('compliance_no_rules', 'local_railvieweradmin'))) . '</p>';
            }
            if ($substrows) {
                $html .= '<table border="1" cellpadding="2" style="font-size:7pt"><thead><tr style="background-color:#e8e8e8;font-weight:bold">'
                    . '<th>' . s(get_string('section', 'local_railvieweradmin')) . '</th>'
                    . '<th>' . s(get_string('subst_replaced', 'local_railvieweradmin')) . '</th>'
                    . '<th align="center">' . s(get_string('compliance_subst_count', 'local_railvieweradmin')) . '</th>'
                    . '<th>' . s(get_string('subst_by', 'local_railvieweradmin')) . '</th>'
                    . '<th>' . s(get_string('compliance_subst_rides', 'local_railvieweradmin')) . '</th></tr></thead><tbody>'
                    . $substrows . '</tbody></table>';
            } else if ($rulelines) {
                $html .= '<p style="font-size:7pt">' . s(get_string('compliance_no_substitution_used', 'local_railvieweradmin')) . '</p>';
            }

            // ---- Einzelne Fahrten mit Datum, Zugnummer, Ausbilder und Entscheidung
            $rides = $DB->get_records_sql(
                "SELECT r.*, m.name AS methodname, t.firstname, t.lastname, t.firstnamephonetic, t.lastnamephonetic, t.middlename, t.alternatename
                   FROM {railviewer_rides} r JOIN {railviewer_methods} m ON m.id = r.methodid
              LEFT JOIN {user} t ON t.id = r.trainerid
                  WHERE r.assignmentid = :aid
               ORDER BY r.ridedate, r.id", ['aid' => $a->id]);
            if ($rides) {
                $html .= '<p style="font-size:8pt"><b>' . s(get_string('compliance_rides_heading', 'local_railvieweradmin')) . '</b></p>'
                    . '<table border="1" cellpadding="2" style="font-size:7pt"><thead><tr style="background-color:#e8e8e8;font-weight:bold">'
                    . '<th width="9%">' . s(get_string('ride_date', 'local_railvieweradmin')) . '</th>'
                    . '<th width="8%">' . s(get_string('ride_train', 'local_railvieweradmin')) . '</th>'
                    . '<th width="12%">' . s(get_string('ride_method', 'local_railvieweradmin')) . '</th>'
                    . '<th width="17%">' . s(get_string('ride_sections', 'local_railvieweradmin')) . '</th>'
                    . '<th width="19%">' . s(get_string('ride_trainer', 'local_railvieweradmin')) . '</th>'
                    . '<th width="10%">' . s(get_string('compliance_recorded_on', 'local_railvieweradmin')) . '</th>'
                    . '<th width="25%">' . s(get_string('compliance_decision', 'local_railvieweradmin')) . '</th></tr></thead><tbody>';
                $secnames = [];
                foreach ($DB->get_records_sql("SELECT rs.id, rs.rideid, s.name FROM {railviewer_ride_sections} rs
                        JOIN {railviewer_sections} s ON s.id = rs.sectionid
                        JOIN {railviewer_rides} r ON r.id = rs.rideid WHERE r.assignmentid = :aid ORDER BY rs.id", ['aid' => $a->id]) as $x) {
                    $secnames[$x->rideid][] = format_string($x->name);
                }
                foreach ($rides as $r) {
                    $trainer = $r->isexternal ? $r->trainername . ' (' . $r->evu . ')' : fullname($r);
                    $sig = '';
                    if ($forpdf && $r->isexternal) {
                        $file = get_file_storage()->get_file(\context_system::instance()->id, 'local_railvieweradmin',
                            'ridesignature', $r->id, '/', 'signature.png');
                        if ($file) {
                            $sig = '<br/><img src="@' . base64_encode($file->get_content()) . '" height="28"/>';
                        }
                    }
                    $names = $secnames[$r->id] ?? [];
                    $range = count($names) > 1 ? $names[0] . ' → ' . end($names) . ' (' . count($names) . ')' : implode('', $names);
                    $line = get_string('ride_status_' . $r->status, 'local_railvieweradmin');
                    if (!empty($r->timeresponded)) {
                        $line .= ' ' . userdate($r->timeresponded, get_string('strftimedatetimeshort', 'langconfig'));
                        $line .= $r->isexternal ? ' (' . get_string('compliance_by_signature', 'local_railvieweradmin') . ')' : '';
                    }
                    if (!empty($r->rejectreason)) {
                        $line .= ' – ' . $r->rejectreason;
                    }
                    $dparts = [s($line)];
                    foreach ($substnotes[$r->id] ?? [] as $note) {
                        $dparts[] = s($note);
                    }
                    $decision = implode('<br/>', $dparts);
                    $html .= '<tr nobr="true"><td>' . s(userdate($r->ridedate, get_string('strftimedatefullshort', 'langconfig'))) . '</td>'
                        . '<td>' . s($r->trainnumber) . '</td><td>' . s(format_string($r->methodname)) . '</td>'
                        . '<td>' . s($range) . '</td>'
                        . '<td>' . s($trainer) . $sig . '</td>'
                        . '<td>' . s(userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig'))) . '</td>'
                        . '<td>' . $decision . '</td></tr>';
                }
                $html .= '</tbody></table>';
            }
        }

        // Abschlusserklärung: Text-Snapshot, Zeitpunkt der Bestätigung und Hinweis auf die elektronische Unterschrift.
        $decl = $DB->get_records('railviewer_declarations', ['userid' => $userid], 'timecreated DESC, id DESC', '*', 0, 1);
        $decl = reset($decl);
        if ($decl) {
            $when = userdate($decl->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
            $html .= '<h3>' . s(get_string('declaration_title', 'local_railvieweradmin')) . '</h3>'
                . '<div style="font-size:8pt;border:1px solid #999999;padding:4px">' . $decl->textsnapshot . '</div>'
                . '<p style="font-size:8pt"><b>' . s(get_string('declaration_status_' . $decl->status, 'local_railvieweradmin', $when)) . '</b>';
            if ($decl->status === 'accepted') {
                $html .= '<br/>' . s(get_string('declaration_esign_note', 'local_railvieweradmin', (object) [
                    'user' => fullname($user), 'time' => $when]));
            } else if (!empty($decl->declinereason)) {
                $html .= '<br/>' . s($decl->declinereason);
            }
            $html .= '</p>';
        } else if (learning::declaration_required()) {
            $html .= '<h3>' . s(get_string('declaration_title', 'local_railvieweradmin')) . '</h3><p>'
                . s(get_string('declaration_status_none', 'local_railvieweradmin')) . '</p>';
        }
        $html .= '<p><b>' . s(get_string(learning::order_complete($userid, $overview) ? 'order_complete' : 'order_open',
            'local_railvieweradmin')) . '</b></p>';
        return $html;
    }
}
