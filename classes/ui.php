<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Gemeinsame HTML-Bausteine für die Darstellung von Fortschritt und Dauer (Nutzeransicht,
 * Admin-Ansicht, Compliance-Seite). Inline-Styles, weil die Plugin-CSS nicht auf allen Seiten geladen ist.
 *
 * @package local_railvieweradmin
 */
class ui {

    /** Matrix Abschnitt × Methode mit "bestätigt/gefordert". */
    public static function matrix(array $status): string {
        if (empty($status['methods'])) {
            return '';
        }
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable';
        $table->head = [get_string('section', 'local_railvieweradmin')];
        foreach ($status['methods'] as $name) {
            $table->head[] = $name;
        }
        foreach ($status['sections'] as $section) {
            $row = [s($section['name'])];
            foreach ($status['methods'] as $methodid => $name) {
                $c = $section['cells'][$methodid];
                $text = $c['confirmed'] . ' / ' . $c['required'];
                if ($c['substituted'] > 0) {
                    $text .= ' ' . get_string('matrix_substituted', 'local_railvieweradmin', $c['substituted']);
                }
                if ($c['pending'] > 0) {
                    $text .= ' ' . get_string('matrix_pending', 'local_railvieweradmin', $c['pending']);
                }
                if (!$c['complete']) {
                    $text .= ' – ' . get_string('matrix_missing', 'local_railvieweradmin', $c['missing']);
                }
                $cell = new \html_table_cell(s($text));
                $cell->attributes['class'] = $c['complete'] ? 'rv-cell-ok' : 'rv-cell-missing';
                $row[] = $cell;
            }
            $table->data[] = $row;
        }
        return \html_writer::table($table);
    }

    /** Textzeile mit Video- und Gesamtstand. */
    public static function video_line(array $status): string {
        $v = $status['video'];
        if ($v['required'] <= 0) {
            return '';
        }
        return \html_writer::div(get_string('video_progress', 'local_railvieweradmin', $v), 'text-muted');
    }

    /** "Es fehlen noch: …" als Liste. */
    public static function missing_list(array $status): string {
        if (empty($status['missing'])) {
            return '';
        }
        $items = '';
        foreach ($status['missing'] as $m) {
            $items .= \html_writer::tag('li', s($m['sectionname'] . ' – ' . $m['count'] . '× ' . $m['methodname']));
        }
        $chips = '';
        foreach ($status['missing'] as $m) {
            $chips .= \html_writer::span(s($m['sectionname'] . ' – ' . $m['count'] . '× ' . $m['methodname']), 'rv-chip');
        }
        return \html_writer::div(\html_writer::tag('strong', get_string('still_missing', 'local_railvieweradmin') . ':') . ' ' . $chips, 'rv-missing');
    }

    /** "Soll 42 h · erbracht 12 h · 29 %". */
    public static function duration_line(array $duration): string {
        if ($duration['soll'] <= 0 && !$duration['incomplete']) {
            return '';
        }
        $percent = $duration['soll'] > 0 ? (int) round(100 * $duration['ist'] / $duration['soll']) : 0;
        $text = get_string('duration_summary', 'local_railvieweradmin', (object) [
            'soll' => learning::format_minutes($duration['soll']),
            'ist' => learning::format_minutes($duration['ist']),
            'rest' => learning::format_minutes(max(0, $duration['soll'] - $duration['ist'])),
            'percent' => $percent,
        ]);
        if ($duration['incomplete']) {
            $text .= ' ' . get_string('duration_incomplete', 'local_railvieweradmin');
        }
        return \html_writer::div($text, 'railviewer-duration');
    }

    /** Statusbadge einer Fahrt. */
    public static function ride_status(string $status): string {
        $classes = ['pending' => 'badge-warning', 'confirmed' => 'badge-success', 'rejected' => 'badge-danger', 'revoked' => 'badge-secondary'];
        return \html_writer::span(get_string('ride_status_' . $status, 'local_railvieweradmin'), 'badge ' . ($classes[$status] ?? ''));
    }

    /**
     * Auffälliger Kasten "Zu bestätigende Fahrten" für Ausbilder (leer, wenn nichts offen ist).
     * Zeigt bis zu 8 Fahrten mit direktem Link zur Bestätigung.
     */
    public static function confirmation_callout(int $userid): string {
        $pending = learning::pending_confirmations($userid);
        if (!$pending) {
            return '';
        }
        $items = '';
        foreach (array_slice($pending, 0, 8, true) as $r) {
            $label = fullname($r) . ' – ' . format_string($r->routename) . ' – ' . format_string($r->methodname) . ' – '
                . userdate($r->ridedate, get_string('strftimedatefullshort', 'langconfig')) . ' – ' . $r->trainnumber;
            $items .= \html_writer::tag('li', \html_writer::link(new \moodle_url('/mod/railviewer/ride_confirm.php', ['id' => $r->id]), s($label)));
        }
        $more = count($pending) > 8 ? \html_writer::div(\html_writer::link(new \moodle_url('/mod/railviewer/rides.php'),
            get_string('rides_to_confirm_more', 'local_railvieweradmin', count($pending) - 8))) : '';
        return \html_writer::div(
            \html_writer::tag('strong', '🔔 ' . get_string('rides_to_confirm_n', 'local_railvieweradmin', count($pending)))
            . \html_writer::tag('ul', $items, ['style' => 'margin:6px 0 0;padding-left:18px']) . $more,
            'alert alert-warning', ['style' => 'margin:12px 0']);
    }
}
