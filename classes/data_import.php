<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/** Fehler in einer Importzeile (wird als Zeilenergebnis gemeldet, bricht den Import nicht ab). */
class import_exception extends \Exception {
}

/**
 * CSV-Datenimport für Stammdaten, Strecken, Anforderungen, Einsatzstellen und Zuweisungen.
 *
 * Regeln: nur neu anlegen (Vorhandenes wird übersprungen, nie überschrieben); Verweise auf andere
 * Datensätze über einen optionalen `key` oder den Namen; Vorschau = identischer Lauf in einer
 * Transaktion, die zurückgerollt wird (InnoDB/PostgreSQL), dadurch sehen abhängige Dateien eines
 * Pakets die in derselben Vorschau neu angelegten Datensätze.
 *
 * @package local_railvieweradmin
 */
class data_import {

    /** Dateitypen in Abhängigkeitsreihenfolge. */
    const TYPES = ['methoden', 'erfahrungstypen', 'abschnitte', 'strecken', 'strecken_abschnitte', 'abschnitt_dauer',
        'anforderungen', 'ersetzungen', 'einsatzstellen', 'einsatzstellen_strecken', 'zuweisungen'];

    /** Alias-Spaltennamen (klein) -> kanonischer Name. */
    const ALIASES = [
        'schluessel' => 'key', 'schlüssel' => 'key', 'beschreibung' => 'description', 'strecke' => 'route',
        'abschnitt' => 'section', 'methode' => 'method', 'anzahl' => 'count', 'benutzer' => 'user', 'nutzer' => 'user',
        'erfahrungstyp' => 'experience_type', 'berufserfahrungstyp' => 'experience_type', 'minuten' => 'minutes',
        'dauer_min' => 'minutes', 'nacht' => 'night', 'aktiv' => 'enabled', 'reihenfolge' => 'sortorder',
        'position' => 'position', 'richtung' => 'direction', 'gegenrichtung' => 'paired_route',
        'videoquelle' => 'video_source', 'videoverweis' => 'video_ref', 'dauer_sek' => 'duration_sec',
        'ersetzt_durch' => 'by_method', 'faktor' => 'factor', 'automatisch' => 'auto', 'erlassen' => 'waive',
        'begruendung' => 'waive_reason', 'begründung' => 'waive_reason', 'name' => 'name',
    ];

    /** Spalten je Typ: kanonischer Name => [Pflicht, Beschreibung(Lang-Key)]. Beispiel-Zeile für die Vorlage. */
    public static function definitions(): array {
        return [
            'methoden' => ['columns' => ['name' => 1, 'night' => 0, 'sortorder' => 0, 'enabled' => 0],
                'sample' => [['Selbstfahren', '0', '1', '1'], ['Mitfahren bei Nacht', '1', '4', '1']]],
            'erfahrungstypen' => ['columns' => ['name' => 1, 'sortorder' => 0, 'enabled' => 0],
                'sample' => [['≤ 12 Monate Berufserfahrung', '1', '1'], ['> 12 Monate Berufserfahrung', '2', '1']]],
            'abschnitte' => ['columns' => ['key' => 0, 'name' => 1, 'description' => 0, 'video_source' => 0, 'video_ref' => 0,
                'duration_sec' => 0, 'location_type' => 0, 'ril100' => 0, 'node_name' => 0],
                'sample' => [['A1', 'Wesel - Emmerich', 'Rheinquerung', 'vimeo', '123456789', '754', '', '', ''],
                    ['A2', 'Emmerich - Kleve', '', 's3', 'railviewer/emmerich_kleve.mp4', '', '', '', '']]],
            'strecken' => ['columns' => ['key' => 0, 'name' => 1, 'description' => 0, 'paired_route' => 0, 'direction' => 0],
                'sample' => [['S1', 'Wesel - Kleve', '', 'S2', 'hin'], ['S2', 'Kleve - Wesel', '', 'S1', 'rueck']]],
            'strecken_abschnitte' => ['columns' => ['route' => 1, 'position' => 0, 'section' => 1],
                'sample' => [['S1', '1', 'A1'], ['S1', '2', 'A2']]],
            'abschnitt_dauer' => ['columns' => ['section' => 1, 'method' => 1, 'minutes' => 1],
                'sample' => [['A1', 'video', '13'], ['A1', 'Selbstfahren', '20']]],
            'anforderungen' => ['columns' => ['route' => 1, 'experience_type' => 0, 'video' => 0, '<Methodenname>' => 0],
                'sample' => [['S1', '≤ 12 Monate Berufserfahrung', '3', '2', '1', '2', '1'],
                    ['S1', '> 12 Monate Berufserfahrung', '1', '1', '0', '1', '0']],
                'header' => ['route', 'experience_type', 'video', 'Selbstfahren', 'Mitfahren', 'Selbstfahren bei Nacht', 'Mitfahren bei Nacht']],
            'ersetzungen' => ['columns' => ['route' => 1, 'method' => 1, 'by_method' => 1, 'factor' => 0, 'auto' => 0],
                'sample' => [['S1', 'Selbstfahren bei Nacht', 'Selbstfahren', '2', '0']]],
            'einsatzstellen' => ['columns' => ['key' => 0, 'name' => 1, 'description' => 0],
                'sample' => [['E1', 'Standort Nord', 'Strecken im Norden']]],
            'einsatzstellen_strecken' => ['columns' => ['einsatzstelle' => 1, 'route' => 1],
                'sample' => [['E1', 'S1'], ['E1', 'S2']]],
            'zuweisungen' => ['columns' => ['user' => 1, 'route' => 0, 'einsatzstelle' => 0, 'both_directions' => 0,
                'experience_type' => 0, 'waive' => 0, 'waive_reason' => 0, 'required_views' => 0],
                'sample' => [['mmuster', '', 'E1', '1', '≤ 12 Monate Berufserfahrung', '0', '', ''],
                    ['mmuster', 'S2', '', '0', '≤ 12 Monate Berufserfahrung', '1', 'kennt die Strecke', '']]],
        ];
    }

    /** Recht, das für Import und Export eines Dateityps nötig ist. */
    public static function required_capability(string $type): string {
        if (in_array($type, ['einsatzstellen', 'einsatzstellen_strecken'], true)) {
            return 'local/railvieweradmin:manageeinsatzstellen';
        }
        if ($type === 'zuweisungen') {
            return 'local/railvieweradmin:assignroutes';
        }
        return 'local/railvieweradmin:editsections';
    }

    /** @return string[] Dateitypen, die der aktuelle Nutzer importieren/exportieren darf. */
    public static function allowed_types(): array {
        $context = \context_system::instance();
        return array_values(array_filter(self::TYPES, function($t) use ($context) {
            return has_capability(self::required_capability($t), $context);
        }));
    }

    /** Läuft ein Trockenlauf mit Rollback sicher auf dieser Datenbank? */
    public static function supports_dry_run(): bool {
        global $DB;
        if ($DB->get_dbfamily() === 'mysql') {
            return method_exists($DB, 'get_dbengine') && in_array($DB->get_dbengine(), ['InnoDB', 'XtraDB', 'Aria'], true) && $DB->get_dbengine() !== 'Aria';
        }
        return true;
    }

    // ---- Einlesen ------------------------------------------------------------------------------

    /** Trennzeichen aus der Kopfzeile erraten. */
    public static function detect_delimiter(string $content): string {
        $first = strtok($content, "\n") ?: '';
        $counts = [';' => substr_count($first, ';'), ',' => substr_count($first, ','), "\t" => substr_count($first, "\t")];
        arsort($counts);
        return key($counts) ?: ';';
    }

    /**
     * @return array ['header' => string[] kanonisch, 'rows' => [ ['line' => n, 'd' => [spalte => wert]] ], 'warnings' => []]
     */
    public static function parse_csv(string $content, string $delimiter, string $encoding): array {
        $content = \core_text::convert($content, $encoding, 'utf-8');
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $delim = $delimiter === 'tab' ? "\t" : ($delimiter === 'comma' ? ',' : ($delimiter === 'semicolon' ? ';' : $delimiter));
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $header = null;
        $rows = [];
        $warnings = [];
        $line = 0;
        while (($rec = fgetcsv($handle, 0, $delim)) !== false) {
            $line++;
            if ($rec === [null] || (count($rec) === 1 && trim((string) $rec[0]) === '')) {
                continue;
            }
            if ($header === null) {
                $header = [];
                foreach ($rec as $h) {
                    $name = trim((string) $h);
                    $low = \core_text::strtolower($name);
                    // Methodennamen (Anforderungen, breit) behalten ihre Schreibweise.
                    $header[] = isset(self::ALIASES[$low]) ? self::ALIASES[$low] : $name;
                }
                continue;
            }
            $d = [];
            foreach ($header as $i => $col) {
                $val = trim((string) ($rec[$i] ?? ''));
                // Export schützt Zellen mit führendem = + @ durch ein Apostroph (Formel-Injection in Tabellenkalkulationen).
                $d[$col] = preg_match("/^'[=+@]/", $val) ? substr($val, 1) : $val;
            }
            $rows[] = ['line' => $line, 'd' => $d];
        }
        fclose($handle);
        return ['header' => $header ?? [], 'rows' => $rows, 'warnings' => $warnings];
    }

    /** Dateityp aus einem Dateinamen ("01_Methoden.csv" -> methoden) oder null. */
    public static function type_from_filename(string $filename): ?string {
        $stem = \core_text::strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $stem = preg_replace('/^[0-9]+[_\-\s]*/', '', $stem);
        $stem = str_replace(['ä', 'ö', 'ü', ' ', '-'], ['ae', 'oe', 'ue', '_', '_'], $stem);
        return in_array($stem, self::TYPES, true) ? $stem : null;
    }

    // ---- Lauf ---------------------------------------------------------------------------------

    /**
     * Führt den Import aus.
     *
     * @param array $files typ => geparste Datei (siehe parse_csv)
     * @param bool $dry true = in Transaktion ausführen und zurückrollen
     * @return array typ => ['warnings' => [], 'results' => [['line','status','message']], 'counts' => [created, skipped, error]]
     */
    public static function run(array $files, bool $dry): array {
        global $DB;
        $ctx = (object) ['createdroutes' => [], 'createdsections' => [], 'createdeinsatz' => [], 'methodcache' => null];
        $output = [];
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach (self::TYPES as $type) {
                if (!isset($files[$type])) {
                    continue;
                }
                $file = $files[$type];
                $defs = self::definitions()[$type]['columns'];
                $warnings = [];
                // Pflichtspalten prüfen.
                $missing = [];
                foreach ($defs as $col => $required) {
                    if ($required && !in_array($col, $file['header'], true)) {
                        $missing[] = $col;
                    }
                }
                if ($type === 'anforderungen') {
                    $missing = in_array('route', $file['header'], true) ? [] : ['route'];
                }
                if ($missing) {
                    $output[$type] = ['warnings' => [get_string('import_missing_columns', 'local_railvieweradmin', implode(', ', $missing))],
                        'results' => [], 'counts' => [0, 0, 0], 'fatal' => true];
                    continue;
                }
                if ($type !== 'anforderungen') {
                    foreach ($file['header'] as $col) {
                        if ($col !== '' && !isset($defs[$col])) {
                            $warnings[] = get_string('import_unknown_column', 'local_railvieweradmin', $col);
                        }
                    }
                }
                $results = self::{'process_' . $type}($file['rows'], $ctx, $warnings, $file['header']);
                $counts = [0, 0, 0];
                foreach ($results as $r) {
                    $counts[$r['status'] === 'created' ? 0 : ($r['status'] === 'skipped' ? 1 : 2)]++;
                }
                $output[$type] = ['warnings' => $warnings, 'results' => $results, 'counts' => $counts];
            }
        } catch (\Throwable $e) {
            try {
                $transaction->rollback($e);
            } catch (\Throwable $ignored) {
                // Rollback meldet die Ausnahme erneut; hier nur zum Abbruch benötigt.
            }
            throw $e;
        }
        if ($dry) {
            try {
                $transaction->rollback(new \moodle_exception('dryrun'));
            } catch (\moodle_exception $e) {
                // gewollt: Vorschau verwirft alle Änderungen
            }
        } else {
            $transaction->allow_commit();
        }
        return $output;
    }

    // ---- Hilfen -------------------------------------------------------------------------------

    private static function ok(int $line, string $msg = ''): array {
        return ['line' => $line, 'status' => 'created', 'message' => $msg];
    }

    private static function skip(int $line, string $msg): array {
        return ['line' => $line, 'status' => 'skipped', 'message' => $msg];
    }

    private static function fail(int $line, string $msg): array {
        return ['line' => $line, 'status' => 'error', 'message' => $msg];
    }

    /** Zeile ohne Eingabe in den auszufüllenden Spalten (z.B. aus einem Export) -> überspringen. */
    private static function blank(array $d, array $cols): bool {
        foreach ($cols as $c) {
            if (trim((string) ($d[$c] ?? '')) !== '') {
                return false;
            }
        }
        return true;
    }

    private static function flag($v, bool $default = false): bool {
        $v = \core_text::strtolower(trim((string) $v));
        if ($v === '') {
            return $default;
        }
        return in_array($v, ['1', 'ja', 'j', 'yes', 'y', 'true', 'x', 'wahr'], true);
    }

    /** Datensatz per key oder Name finden; null wenn nicht vorhanden, Exception bei Mehrdeutigkeit. */
    private static function lookup(string $table, string $ref, bool $haskey = true): ?\stdClass {
        global $DB;
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }
        if ($haskey) {
            $found = $DB->get_records_select($table, $DB->sql_equal('importkey', ':k', false, false), ['k' => $ref]);
            if (count($found) === 1) {
                return reset($found);
            }
            if (count($found) > 1) {
                throw new import_exception(get_string('import_ambiguous', 'local_railvieweradmin', $ref));
            }
        }
        $found = $DB->get_records_select($table, $DB->sql_equal('name', ':n', false, false), ['n' => $ref]);
        if (count($found) > 1) {
            throw new import_exception(get_string('import_ambiguous', 'local_railvieweradmin', $ref));
        }
        return $found ? reset($found) : null;
    }

    private static function need(string $table, string $ref, string $label, bool $haskey = true): \stdClass {
        $rec = self::lookup($table, $ref, $haskey);
        if (!$rec) {
            throw new import_exception(get_string('import_not_found', 'local_railvieweradmin', (object) ['what' => $label, 'ref' => $ref]));
        }
        return $rec;
    }

    /** Methode per Name; "video" = 0. */
    private static function method_id(string $ref): int {
        $ref = trim($ref);
        if (\core_text::strtolower($ref) === 'video') {
            return 0;
        }
        $m = self::lookup('railviewer_methods', $ref, false);
        if (!$m) {
            throw new import_exception(get_string('import_not_found', 'local_railvieweradmin',
                (object) ['what' => get_string('learning_methods', 'local_railvieweradmin'), 'ref' => $ref]));
        }
        return (int) $m->id;
    }

    /** Berufserfahrungstyp per Name; leer = einziger aktiver Typ. */
    private static function type_id(string $ref): int {
        global $DB;
        $ref = trim($ref);
        if ($ref === '') {
            $all = $DB->get_records('railviewer_experience_types', ['enabled' => 1]);
            if (count($all) === 1) {
                return (int) reset($all)->id;
            }
            throw new import_exception(get_string('import_type_required', 'local_railvieweradmin'));
        }
        $t = self::lookup('railviewer_experience_types', $ref, false);
        if (!$t) {
            throw new import_exception(get_string('import_not_found', 'local_railvieweradmin',
                (object) ['what' => get_string('experience_type', 'local_railvieweradmin'), 'ref' => $ref]));
        }
        return (int) $t->id;
    }

    private static function video_fields(\stdClass $rec, string $source, string $ref): void {
        $source = \core_text::strtolower($source);
        if ($source === '' && $ref === '') {
            return;
        }
        if (!in_array($source, ['vimeo', 'ignite', 'sharepoint', 's3'], true)) {
            throw new import_exception(get_string('import_err_source', 'local_railvieweradmin'));
        }
        if ($ref === '') {
            return;
        }
        $ok = true;
        if ($source === 'vimeo') {
            $ok = ctype_digit($ref) && strlen($ref) <= 10;
            $rec->vimeo_video_id = (int) $ref;
        } else if ($source === 'ignite') {
            $ok = (bool) preg_match('/^[A-Za-z0-9_-]{1,64}$/', $ref);
            $rec->ignite_video_id = $ref;
        } else if ($source === 'sharepoint') {
            $parts = explode('|', $ref);
            $ok = count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '' && strlen($parts[0]) <= 128 && strlen($parts[1]) <= 128;
            if ($ok) {
                [$rec->sharepoint_drive_id, $rec->sharepoint_item_id] = $parts;
            }
        } else {
            $ok = s3_client::is_valid_key($ref);
            $rec->s3_object_key = $ref;
        }
        if (!$ok) {
            throw new import_exception(get_string('import_err_ref', 'local_railvieweradmin', $source));
        }
        $rec->video_source = $source;
    }

    // ---- Verarbeitung je Dateityp -------------------------------------------------------------

    private static function process_methoden(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $name = local_railvieweradmin_clean_label($d['name'] ?? '');
                if ($name === '') {
                    throw new import_exception(get_string('import_err_name', 'local_railvieweradmin'));
                }
                if (self::lookup('railviewer_methods', $name, false)) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $base = preg_replace('/[^a-z0-9_]+/', '_', \core_text::strtolower($name));
                $DB->insert_record('railviewer_methods', (object) [
                    'name' => $name, 'shortname' => substr($base, 0, 40) . '_' . substr(md5(uniqid('', true)), 0, 6),
                    'night' => self::flag($d['night'] ?? '') ? 1 : 0,
                    'sortorder' => ($d['sortorder'] ?? '') !== '' ? (int) $d['sortorder'] : (int) $DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {railviewer_methods}'),
                    'enabled' => self::flag($d['enabled'] ?? '', true) ? 1 : 0,
                ]);
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_erfahrungstypen(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $name = local_railvieweradmin_clean_label($d['name'] ?? '');
                if ($name === '') {
                    throw new import_exception(get_string('import_err_name', 'local_railvieweradmin'));
                }
                if (self::lookup('railviewer_experience_types', $name, false)) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $DB->insert_record('railviewer_experience_types', (object) [
                    'name' => $name,
                    'sortorder' => ($d['sortorder'] ?? '') !== '' ? (int) $d['sortorder'] : (int) $DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {railviewer_experience_types}'),
                    'enabled' => self::flag($d['enabled'] ?? '', true) ? 1 : 0,
                ]);
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_abschnitte(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $name = trim($d['name'] ?? '');
                if ($name === '') {
                    throw new import_exception(get_string('import_err_name', 'local_railvieweradmin'));
                }
                $key = trim($d['key'] ?? '');
                if (($key !== '' && self::lookup('railviewer_sections', $key)) || self::lookup('railviewer_sections', $name, false)) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $rec = (object) ['name' => $name, 'description' => ($d['description'] ?? '') !== '' ? $d['description'] : null,
                    'duration' => max(0, (int) ($d['duration_sec'] ?? 0)), 'video_source' => 'upload',
                    'importkey' => $key !== '' ? $key : null, 'timecreated' => time(), 'timemodified' => time()];
                self::video_fields($rec, $d['video_source'] ?? '', $d['video_ref'] ?? '');
                if (\core_text::strtolower($d['location_type'] ?? '') === 'node') {
                    if (($d['ril100'] ?? '') === '') {
                        throw new import_exception(get_string('import_err_ril', 'local_railvieweradmin'));
                    }
                    $rec->location_type = 'node';
                    $rec->node_ril100 = clean_param($d['ril100'], PARAM_ALPHANUMEXT);
                    $rec->node_name = ($d['node_name'] ?? '') !== '' ? $d['node_name'] : null;
                }
                $id = $DB->insert_record('railviewer_sections', $rec);
                $ctx->createdsections[$id] = true;
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_strecken(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        $pairing = [];
        foreach ($rows as $i => $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $name = trim($d['name'] ?? '');
                if ($name === '') {
                    throw new import_exception(get_string('import_err_name', 'local_railvieweradmin'));
                }
                $key = trim($d['key'] ?? '');
                $existing = ($key !== '' ? self::lookup('railviewer_routes', $key) : null) ?: self::lookup('railviewer_routes', $name, false);
                if ($existing) {
                    $res[$i] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $direction = \core_text::strtolower($d['direction'] ?? '');
                if ($direction !== '' && !in_array($direction, ['hin', 'rueck'], true)) {
                    throw new import_exception(get_string('import_err_direction', 'local_railvieweradmin'));
                }
                $id = $DB->insert_record('railviewer_routes', (object) ['name' => $name, 'description' => $d['description'] ?? '',
                    'importkey' => $key !== '' ? $key : null]);
                $ctx->createdroutes[$id] = true;
                if (($d['paired_route'] ?? '') !== '') {
                    $pairing[$i] = [$id, $d['paired_route'], $direction ?: 'hin'];
                }
                $res[$i] = self::ok($line);
            } catch (import_exception $e) {
                $res[$i] = self::fail($line, $e->getMessage());
            }
        }
        // Zweiter Durchlauf: Hin-/Rückfahrt-Paare verknüpfen, wenn beide Seiten noch unverknüpft sind.
        foreach ($pairing as $i => [$routeid, $partnerref, $direction]) {
            try {
                $partner = self::need('railviewer_routes', $partnerref, get_string('route', 'local_railvieweradmin'));
                $self = $DB->get_record('railviewer_routes', ['id' => $routeid]);
                if ((int) $partner->id === (int) $routeid) {
                    throw new import_exception(get_string('import_err_selfpair', 'local_railvieweradmin'));
                }
                if ((int) $self->pairedrouteid === (int) $partner->id) {
                    continue; // bereits von der Gegenseite verknüpft
                }
                if (!empty($self->pairedrouteid) || !empty($partner->pairedrouteid)) {
                    throw new import_exception(get_string('import_err_alreadypaired', 'local_railvieweradmin'));
                }
                local_railvieweradmin_update_route_pairing($routeid, (int) $partner->id, $direction);
            } catch (import_exception $e) {
                $res[$i] = self::fail($rows[$i]['line'], $e->getMessage());
            }
        }
        ksort($res);
        return array_values($res);
    }

    private static function process_strecken_abschnitte(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        $groups = [];
        foreach ($rows as $i => $row) {
            $d = $row['d'];
            if (self::blank($d, ['section'])) {
                $res[$i] = self::skip($row['line'], get_string('import_empty_row', 'local_railvieweradmin'));
                continue;
            }
            try {
                $route = self::need('railviewer_routes', $d['route'] ?? '', get_string('route', 'local_railvieweradmin'));
                $section = self::need('railviewer_sections', $d['section'] ?? '', get_string('section', 'local_railvieweradmin'));
                $pos = ($d['position'] ?? '') !== '' ? (int) $d['position'] : $i + 1;
                $groups[$route->id][] = ['i' => $i, 'pos' => $pos, 'sectionid' => (int) $section->id, 'line' => $row['line']];
            } catch (import_exception $e) {
                $res[$i] = self::fail($row['line'], $e->getMessage());
            }
        }
        foreach ($groups as $routeid => $items) {
            $existing = $DB->count_records('railviewer_route_sections', ['routeid' => $routeid]);
            if ($existing && empty($ctx->createdroutes[$routeid])) {
                foreach ($items as $it) {
                    $res[$it['i']] = self::skip($it['line'], get_string('import_route_has_sections', 'local_railvieweradmin'));
                }
                continue;
            }
            usort($items, function($a, $b) { return $a['pos'] <=> $b['pos'] ?: $a['i'] <=> $b['i']; });
            $seen = [];
            $sort = 0;
            foreach ($items as $it) {
                if (isset($seen[$it['sectionid']])) {
                    $res[$it['i']] = self::fail($it['line'], get_string('import_err_duplicate_section', 'local_railvieweradmin'));
                    continue;
                }
                $seen[$it['sectionid']] = true;
                $DB->insert_record('railviewer_route_sections', (object) ['routeid' => $routeid, 'sectionid' => $it['sectionid'], 'sortorder' => $sort++]);
                $res[$it['i']] = self::ok($it['line']);
            }
        }
        ksort($res);
        return array_values($res);
    }

    private static function process_abschnitt_dauer(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                if (self::blank($d, ['minutes'])) {
                    $res[] = self::skip($line, get_string('import_empty_row', 'local_railvieweradmin'));
                    continue;
                }
                $section = self::need('railviewer_sections', $d['section'] ?? '', get_string('section', 'local_railvieweradmin'));
                $methodid = self::method_id($d['method'] ?? '');
                if (!isset($d['minutes']) || !ctype_digit($d['minutes']) || (int) $d['minutes'] < 1) {
                    throw new import_exception(get_string('import_err_minutes', 'local_railvieweradmin'));
                }
                if ($DB->record_exists('railviewer_section_durations', ['sectionid' => $section->id, 'methodid' => $methodid])) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $DB->insert_record('railviewer_section_durations', (object) ['sectionid' => $section->id, 'methodid' => $methodid, 'minutes' => (int) $d['minutes']]);
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    /** Anzahl Lernfahrten je Strecke, Berufserfahrungstyp und Methode – lange oder breite Form. */
    private static function process_anforderungen(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        $long = in_array('method', $header, true) && in_array('count', $header, true);
        $fixed = ['route', 'experience_type', 'key', 'method', 'count'];
        $methodcols = [];
        if (!$long) {
            foreach ($header as $col) {
                if ($col === '' || in_array($col, $fixed, true)) {
                    continue;
                }
                try {
                    $methodcols[$col] = self::method_id($col);
                } catch (import_exception $e) {
                    $warnings[] = get_string('import_unknown_column', 'local_railvieweradmin', $col);
                }
            }
        }
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $route = self::need('railviewer_routes', $d['route'] ?? '', get_string('route', 'local_railvieweradmin'));
                $typeid = self::type_id($d['experience_type'] ?? '');
                $cells = [];
                if ($long) {
                    $cells[self::method_id($d['method'] ?? '')] = $d['count'] ?? '';
                } else {
                    foreach ($methodcols as $col => $mid) {
                        $cells[$mid] = $d[$col] ?? '';
                    }
                }
                $created = 0;
                $existing = 0;
                foreach ($cells as $mid => $value) {
                    if ($value === '') {
                        continue;
                    }
                    if (!ctype_digit($value)) {
                        throw new import_exception(get_string('import_err_count', 'local_railvieweradmin', $value));
                    }
                    if ($DB->record_exists('railviewer_route_reqs', ['routeid' => $route->id, 'methodid' => $mid, 'experiencetypeid' => $typeid])) {
                        $existing++;
                        continue;
                    }
                    $DB->insert_record('railviewer_route_reqs', (object) ['routeid' => $route->id, 'methodid' => $mid,
                        'experiencetypeid' => $typeid, 'required_count' => (int) $value]);
                    $created++;
                }
                $res[] = $created ? self::ok($line, get_string('import_requirements_summary', 'local_railvieweradmin', (object) ['new' => $created, 'old' => $existing]))
                    : self::skip($line, get_string($existing ? 'import_exists' : 'import_empty_row', 'local_railvieweradmin'));
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_ersetzungen(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            if (self::blank($d, ['method', 'by_method'])) {
                $res[] = self::skip($line, get_string('import_empty_row', 'local_railvieweradmin'));
                continue;
            }
            try {
                $route = self::need('railviewer_routes', $d['route'] ?? '', get_string('route', 'local_railvieweradmin'));
                $m = self::method_id($d['method'] ?? '');
                $by = self::method_id($d['by_method'] ?? '');
                if ($m === 0 || $by === 0 || $m === $by) {
                    throw new import_exception(get_string('subst_invalid', 'local_railvieweradmin'));
                }
                $factor = ($d['factor'] ?? '') !== '' ? (int) $d['factor'] : 1;
                if ($factor < 1) {
                    throw new import_exception(get_string('subst_invalid', 'local_railvieweradmin'));
                }
                if ($DB->record_exists('railviewer_route_substs', ['routeid' => $route->id, 'methodid' => $m, 'bymethodid' => $by])) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                if ($DB->record_exists('railviewer_route_substs', ['routeid' => $route->id, 'methodid' => $by, 'bymethodid' => $m])) {
                    throw new import_exception(get_string('subst_cycle', 'local_railvieweradmin'));
                }
                $DB->insert_record('railviewer_route_substs', (object) ['routeid' => $route->id, 'methodid' => $m, 'bymethodid' => $by,
                    'factor' => $factor, 'userchoice' => self::flag($d['auto'] ?? '') ? 0 : 1,
                    'sortorder' => $DB->count_records('railviewer_route_substs', ['routeid' => $route->id])]);
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_einsatzstellen(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            try {
                $name = trim($d['name'] ?? '');
                if ($name === '') {
                    throw new import_exception(get_string('import_err_name', 'local_railvieweradmin'));
                }
                $key = trim($d['key'] ?? '');
                if (($key !== '' && self::lookup('railviewer_einsatzstellen', $key)) || self::lookup('railviewer_einsatzstellen', $name, false)) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $id = $DB->insert_record('railviewer_einsatzstellen', (object) ['name' => $name, 'description' => $d['description'] ?? '',
                    'importkey' => $key !== '' ? $key : null, 'timecreated' => time(), 'timemodified' => time()]);
                $ctx->createdeinsatz[$id] = true;
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_einsatzstellen_strecken(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB;
        $res = [];
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            if (self::blank($d, ['route'])) {
                $res[] = self::skip($line, get_string('import_empty_row', 'local_railvieweradmin'));
                continue;
            }
            try {
                $es = self::need('railviewer_einsatzstellen', $d['einsatzstelle'] ?? '', get_string('einsatzstelle', 'local_railvieweradmin'));
                $route = self::need('railviewer_routes', $d['route'] ?? '', get_string('route', 'local_railvieweradmin'));
                if ($DB->record_exists('railviewer_einsatz_routes', ['einsatzstelleid' => $es->id, 'routeid' => $route->id])) {
                    $res[] = self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
                    continue;
                }
                $DB->insert_record('railviewer_einsatz_routes', (object) ['einsatzstelleid' => $es->id, 'routeid' => $route->id,
                    'sortorder' => $DB->count_records('railviewer_einsatz_routes', ['einsatzstelleid' => $es->id])]);
                $res[] = self::ok($line);
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }

    private static function process_zuweisungen(array $rows, $ctx, array &$warnings, array $header): array {
        global $DB, $USER, $CFG;
        $res = [];
        $learning = learning::enabled();
        foreach ($rows as $row) {
            $d = $row['d'];
            $line = $row['line'];
            if (self::blank($d, ['route', 'einsatzstelle'])) {
                $res[] = self::skip($line, get_string('import_empty_row', 'local_railvieweradmin'));
                continue;
            }
            try {
                $uref = trim($d['user'] ?? '');
                $user = $DB->get_record('user', ['username' => \core_text::strtolower($uref), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0]);
                if (!$user && $uref !== '') {
                    $byid = $DB->get_records('user', ['idnumber' => $uref, 'deleted' => 0]);
                    if (count($byid) > 1) {
                        throw new import_exception(get_string('import_ambiguous', 'local_railvieweradmin', $uref));
                    }
                    $user = $byid ? reset($byid) : null;
                }
                if (!$user) {
                    throw new import_exception(get_string('import_not_found', 'local_railvieweradmin',
                        (object) ['what' => get_string('user'), 'ref' => $uref]));
                }
                $esid = 0;
                $routeids = [];
                if (($d['einsatzstelle'] ?? '') !== '') {
                    $esid = (int) self::need('railviewer_einsatzstellen', $d['einsatzstelle'], get_string('einsatzstelle', 'local_railvieweradmin'))->id;
                }
                if (($d['route'] ?? '') !== '') {
                    $routeids[] = (int) self::need('railviewer_routes', $d['route'], get_string('route', 'local_railvieweradmin'))->id;
                }
                if (!$esid && !$routeids) {
                    throw new import_exception(get_string('select_route_or_einsatzstelle', 'local_railvieweradmin'));
                }
                $routeids = learning::resolve_routes($routeids, $esid, self::flag($d['both_directions'] ?? ''));
                if (!$routeids) {
                    throw new import_exception(get_string('import_einsatz_empty', 'local_railvieweradmin'));
                }
                $waive = self::flag($d['waive'] ?? '');
                if ($waive && trim($d['waive_reason'] ?? '') === '') {
                    throw new import_exception(get_string('import_err_waive_reason', 'local_railvieweradmin'));
                }
                $typeid = $learning ? self::type_id($d['experience_type'] ?? '') : 0;
                $views = ($d['required_views'] ?? '') !== '' ? max(1, (int) $d['required_views']) : 1;
                $created = 0;
                $existing = 0;
                foreach ($routeids as $rid) {
                    if ($DB->record_exists('railviewer_assignments', ['userid' => $user->id, 'routeid' => $rid])) {
                        $existing++;
                        continue;
                    }
                    if ($learning) {
                        learning::merge_assignment((int) $user->id, $rid, $typeid, $views, $waive, trim($d['waive_reason'] ?? ''),
                            (int) $USER->id, $esid ?: null);
                    } else {
                        $DB->insert_record('railviewer_assignments', (object) ['userid' => $user->id, 'routeid' => $rid,
                            'required_views' => $views, 'completed_views' => 0]);
                    }
                    $created++;
                }
                $res[] = $created ? self::ok($line, get_string('import_assign_summary', 'local_railvieweradmin', (object) ['new' => $created, 'old' => $existing]))
                    : self::skip($line, get_string('import_exists', 'local_railvieweradmin'));
            } catch (import_exception $e) {
                $res[] = self::fail($line, $e->getMessage());
            }
        }
        return $res;
    }
}
