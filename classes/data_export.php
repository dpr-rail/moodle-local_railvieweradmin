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
 * Export im Importformat. Zwei Modi:
 *  - 'missing':  Vorlage mit allen Verweisen auf bereits vorhandene Daten und leeren Zellen für das, was noch fehlt
 *                (bei Wiederimport werden nur die ausgefüllten Zeilen/Zellen angelegt);
 *  - 'existing': Bestand zur Ansicht/Weiterverarbeitung (beim Wiederimport wird alles als "vorhanden" übersprungen).
 *
 * @package local_railvieweradmin
 */
class data_export {

    /** Verweis auf einen Datensatz: key, falls vorhanden, sonst Name. */
    private static function ref(\stdClass $rec): string {
        return !empty($rec->importkey) ? $rec->importkey : (string) $rec->name;
    }

    /** @return array ['header' => string[], 'rows' => string[][]] */
    public static function build(string $type, string $mode): array {
        global $DB;
        $defs = data_import::definitions()[$type];
        $header = $defs['header'] ?? array_keys($defs['columns']);
        $rows = [];
        $existing = ($mode === 'existing');

        switch ($type) {
            case 'methoden':
                if ($existing) {
                    foreach (learning::methods(false) as $m) {
                        $rows[] = [$m->name, (int) $m->night, (int) $m->sortorder, (int) $m->enabled];
                    }
                }
                break;
            case 'erfahrungstypen':
                if ($existing) {
                    foreach (learning::experience_types(false) as $t) {
                        $rows[] = [$t->name, (int) $t->sortorder, (int) $t->enabled];
                    }
                }
                break;
            case 'abschnitte':
                if ($existing) {
                    foreach ($DB->get_records('railviewer_sections', null, 'name ASC') as $s) {
                        $src = (string) ($s->video_source ?? '');
                        $ref = $src === 'vimeo' ? $s->vimeo_video_id : ($src === 'ignite' ? $s->ignite_video_id
                            : ($src === 'sharepoint' ? $s->sharepoint_drive_id . '|' . $s->sharepoint_item_id : ($src === 's3' ? $s->s3_object_key : '')));
                        $rows[] = [$s->importkey, $s->name, $s->description, in_array($src, ['vimeo', 'ignite', 'sharepoint', 's3'], true) ? $src : '',
                            $ref, (int) $s->duration ?: '', $s->location_type === 'node' ? 'node' : '', $s->node_ril100, $s->node_name];
                    }
                }
                break;
            case 'strecken':
                if ($existing) {
                    foreach ($DB->get_records('railviewer_routes', null, 'name ASC') as $r) {
                        $partner = $r->pairedrouteid ? $DB->get_record('railviewer_routes', ['id' => $r->pairedrouteid]) : null;
                        $rows[] = [$r->importkey, $r->name, $r->description, $partner ? self::ref($partner) : '', $partner ? $r->direction : ''];
                    }
                }
                break;
            case 'strecken_abschnitte':
                foreach ($DB->get_records('railviewer_routes', null, 'name ASC') as $r) {
                    $secs = learning::route_sections((int) $r->id);
                    if ($existing) {
                        $pos = 0;
                        foreach ($secs as $sec) {
                            $full = $DB->get_record('railviewer_sections', ['id' => $sec->id]);
                            $rows[] = [self::ref($r), ++$pos, self::ref($full)];
                        }
                    } else if (!$secs) {
                        $rows[] = [self::ref($r), '', ''];
                    }
                }
                break;
            case 'abschnitt_dauer':
                $have = [];
                foreach ($DB->get_records('railviewer_section_durations') as $d) {
                    $have[$d->sectionid][$d->methodid] = (int) $d->minutes;
                }
                $methods = [0 => 'video'];
                foreach (learning::methods() as $m) {
                    $methods[$m->id] = $m->name;
                }
                foreach ($DB->get_records('railviewer_sections', null, 'name ASC') as $s) {
                    foreach ($methods as $mid => $mname) {
                        if ($existing && isset($have[$s->id][$mid])) {
                            $rows[] = [self::ref($s), $mname, $have[$s->id][$mid]];
                        } else if (!$existing && !isset($have[$s->id][$mid])) {
                            $rows[] = [self::ref($s), $mname, ''];
                        }
                    }
                }
                break;
            case 'anforderungen':
                // Breite Form: eine Zeile je Strecke x Berufserfahrungstyp, eine Spalte je Methode.
                $methods = [0 => 'video'];
                foreach (learning::methods() as $m) {
                    $methods[$m->id] = $m->name;
                }
                $header = array_merge(['route', 'experience_type'], array_values($methods));
                $reqs = [];
                foreach ($DB->get_records('railviewer_route_reqs') as $q) {
                    $reqs[$q->routeid][$q->experiencetypeid][$q->methodid] = (int) $q->required_count;
                }
                foreach ($DB->get_records('railviewer_routes', null, 'name ASC') as $r) {
                    foreach (learning::experience_types() as $t) {
                        $row = [self::ref($r), $t->name];
                        $blank = 0;
                        foreach ($methods as $mid => $unused) {
                            $v = $reqs[$r->id][$t->id][$mid] ?? '';
                            $blank += $v === '' ? 1 : 0;
                            $row[] = $v;
                        }
                        if ($existing ? $blank < count($methods) : $blank > 0) {
                            $rows[] = $row;
                        }
                    }
                }
                break;
            case 'ersetzungen':
                $allm = learning::methods(false);
                foreach ($DB->get_records('railviewer_routes', null, 'name ASC') as $r) {
                    $rules = $DB->get_records('railviewer_route_substs', ['routeid' => $r->id], 'sortorder ASC');
                    if ($existing) {
                        foreach ($rules as $q) {
                            if (isset($allm[$q->methodid], $allm[$q->bymethodid])) {
                                $rows[] = [self::ref($r), $allm[$q->methodid]->name, $allm[$q->bymethodid]->name, (int) $q->factor, $q->userchoice ? 0 : 1];
                            }
                        }
                    } else {
                        $rows[] = [self::ref($r), '', '', '', ''];
                    }
                }
                break;
            case 'einsatzstellen':
                if ($existing) {
                    foreach ($DB->get_records('railviewer_einsatzstellen', null, 'name ASC') as $e) {
                        $rows[] = [$e->importkey, $e->name, $e->description];
                    }
                }
                break;
            case 'einsatzstellen_strecken':
                foreach ($DB->get_records('railviewer_einsatzstellen', null, 'name ASC') as $e) {
                    if ($existing) {
                        foreach ($DB->get_records_sql("SELECT r.* FROM {railviewer_einsatz_routes} er JOIN {railviewer_routes} r ON r.id = er.routeid
                                WHERE er.einsatzstelleid = ? ORDER BY er.sortorder", [$e->id]) as $r) {
                            $rows[] = [self::ref($e), self::ref($r)];
                        }
                    } else {
                        $rows[] = [self::ref($e), ''];
                    }
                }
                break;
            case 'zuweisungen':
                if ($existing) {
                    $learning = learning::enabled();
                    $sql = "SELECT ra.id, u.username, r.name AS routename, r.importkey, ra.waived, ra.waivereason, ra.required_views, ra.experiencetypeid
                              FROM {railviewer_assignments} ra JOIN {user} u ON u.id = ra.userid JOIN {railviewer_routes} r ON r.id = ra.routeid
                             WHERE u.deleted = 0 ORDER BY u.username, r.name";
                    foreach ($DB->get_records_sql($sql) as $a) {
                        $type = $a->experiencetypeid ? (string) $DB->get_field('railviewer_experience_types', 'name', ['id' => $a->experiencetypeid]) : '';
                        $rows[] = [$a->username, $a->importkey ?: $a->routename, '', 0, $type, (int) $a->waived, $a->waivereason, $a->required_views];
                    }
                } else {
                    // Nutzer ohne jede Zuweisung als Ausfüllvorlage (max. 2000).
                    $sql = "SELECT u.id, u.username FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0 AND u.id <> :guest
                               AND NOT EXISTS (SELECT 1 FROM {railviewer_assignments} ra WHERE ra.userid = u.id) ORDER BY u.username";
                    foreach ($DB->get_records_sql($sql, ['guest' => $GLOBALS['CFG']->siteguest], 0, 2000) as $u) {
                        $rows[] = [$u->username, '', '', 0, '', 0, '', ''];
                    }
                }
                break;
        }
        // NULL -> leer.
        $rows = array_map(function($r) { return array_map(function($v) { return $v === null ? '' : (string) $v; }, $r); }, $rows);
        return ['header' => $header, 'rows' => $rows];
    }

    /** CSV-Text (UTF-8 mit BOM für Excel, Semikolon). */
    public static function to_csv(array $data): string {
        // Zellen, die mit = + @ beginnen, würden Excel/LibreOffice als Formel ausführen (CSV-/Formel-Injection
        // über Namen): mit führendem Apostroph entschärfen; der Import entfernt es wieder.
        $safe = function(array $row) {
            return array_map(function($v) {
                $v = (string) $v;
                return $v !== '' && strpos('=+@', $v[0]) !== false ? "'" . $v : $v;
            }, $row);
        };
        $h = fopen('php://temp', 'r+');
        fputcsv($h, $safe($data['header']), ';');
        foreach ($data['rows'] as $row) {
            fputcsv($h, $safe($row), ';');
        }
        rewind($h);
        $csv = stream_get_contents($h);
        fclose($h);
        return "\xEF\xBB\xBF" . $csv;
    }
}
