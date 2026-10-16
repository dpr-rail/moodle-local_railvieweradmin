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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade-Schritte für local_railvieweradmin.
 *
 * db/install.xml war seit längerem nicht mehr synchron mit dem tatsächlich produktiv
 * genutzten Datenbankschema (mehrere Felder/Tabellen wurden offenbar direkt in der DB
 * angelegt, ohne sie hier zu deklarieren). Alle Schritte prüfen daher zuerst, ob das
 * jeweilige Feld/die Tabelle schon existiert, bevor etwas angelegt wird - so schadet
 * dieses Upgrade weder einer Instanz, die diese Spalten/Tabellen bereits hat, noch einer,
 * die (z.B. bei einer frischen Installation über install.xml) alles schon besitzt.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_railvieweradmin_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 202609130000) {

        // railviewer_sections: vimeo_video_id, video_source, ignite_video_id
        $table = new xmldb_table('railviewer_sections');
        $sectionfields = [
            new xmldb_field('vimeo_video_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'videofileitemid'),
            new xmldb_field('video_source', XMLDB_TYPE_CHAR, '20', null, null, null, 'upload', 'vimeo_video_id'),
            new xmldb_field('ignite_video_id', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'video_source'),
        ];
        foreach ($sectionfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // railviewer_overlays: fehlende Style-/Marker-Felder ergänzen.
        $table = new xmldb_table('railviewer_overlays');
        $overlayfields = [
            new xmldb_field('fontsize', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name'),
            new xmldb_field('fontcolor', XMLDB_TYPE_TEXT, null, null, null, null, null, 'fontsize'),
            new xmldb_field('backgroundcolor', XMLDB_TYPE_TEXT, null, null, null, null, null, 'fontcolor'),
            new xmldb_field('bordercolor', XMLDB_TYPE_TEXT, null, null, null, null, null, 'backgroundcolor'),
            new xmldb_field('markerposition', XMLDB_TYPE_TEXT, null, null, null, null, null, 'bordercolor'),
            new xmldb_field('fixedboxwidth', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'markerposition'),
        ];
        foreach ($overlayfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // railviewer_stops: neue, eigenständige Tabelle für Stopp-Punkte.
        $table = new xmldb_table('railviewer_stops');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('stoptime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // Kein separater add_index() auf 'sectionid' hier: der Foreign Key erzeugt in
            // Moodle bereits automatisch einen Index auf derselben Spalte. Ein zusätzlicher
            // expliziter Index auf exakt dieselben Felder wäre ein Duplikat und lässt
            // xmldb_table::addIndex() mit "Key ... collides with index ..." abbrechen.
            $table->add_key('sectionid_fk', XMLDB_KEY_FOREIGN, ['sectionid'], 'railviewer_sections', ['id']);
            $dbman->create_table($table);
        }

        // Migration: bisherige "Stop-Overlays" (railviewer_overlays.is_stop = 1) in die neue
        // railviewer_stops-Tabelle übernehmen, bevor das is_stop-Feld entfernt wird. Diese
        // Funktionalität wurde konsolidiert: play.php liest Stopps künftig ausschließlich aus
        // railviewer_stops statt aus is_stop-markierten Overlays.
        $overlaystable = new xmldb_table('railviewer_overlays');
        $isstopfield = new xmldb_field('is_stop');
        if ($dbman->field_exists($overlaystable, $isstopfield)) {
            $stopoverlays = $DB->get_records_select('railviewer_overlays', 'is_stop = ?', [1]);
            foreach ($stopoverlays as $overlay) {
                $exists = $DB->record_exists('railviewer_stops', [
                    'sectionid' => $overlay->sectionid,
                    'stoptime' => $overlay->starttime,
                ]);
                if (!$exists) {
                    $stop = (object)[
                        'sectionid' => $overlay->sectionid,
                        'name' => $overlay->name,
                        'stoptime' => $overlay->starttime,
                    ];
                    $DB->insert_record('railviewer_stops', $stop);
                }
            }
            // is_stop wird nicht mehr verwendet (Overlays und Stopps sind jetzt getrennte
            // Konzepte) - Feld erst nach erfolgter Migration entfernen.
            $dbman->drop_field($overlaystable, $isstopfield);
        }

        // railviewer_ebulas: hochgeladene EBuLa-Rohdaten.
        $table = new xmldb_table('railviewer_ebulas');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('xml', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
            $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        } else {
            $createdatfield = new xmldb_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $createdatfield)) {
                $dbman->add_field($table, $createdatfield);
            }
        }

        // railviewer_ebula_section: einem Abschnitt zugeordneter Fahrplan-Ausschnitt.
        $table = new xmldb_table('railviewer_ebula_section');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('html', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
            $table->add_field('json', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
            $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // foreign-unique statt foreign + separatem UNIQUE-Index: erzwingt weiterhin
            // "höchstens ein Ausschnitt pro Abschnitt", aber als ein einziger Key statt
            // Key+Index auf denselben Feldern (siehe Kommentar bei railviewer_stops oben).
            $table->add_key('sectionid_fk', XMLDB_KEY_FOREIGN_UNIQUE, ['sectionid'], 'railviewer_sections', ['id']);
            $dbman->create_table($table);
        }

        // railviewer_ebula_section_map: Zeit-zu-Position-Mapping für die "gelbe Linie".
        $table = new xmldb_table('railviewer_ebula_section_map');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('videotime', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('line_top', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('is_static', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('sectionid_fk', XMLDB_KEY_FOREIGN, ['sectionid'], 'railviewer_sections', ['id']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 202609130000, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130002) {

        // Streckenkarten-Widget: railviewer_sections bekommt eine zusaetzliche, optionale
        // geografische Einordnung (Kante(n)/Knoten) obendrauf - rein additiv, bestehende
        // Abschnitte bleiben mit location_type = NULL unveraendert nutzbar.
        $table = new xmldb_table('railviewer_sections');
        $sectionfields = [
            new xmldb_field('location_type', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'duration'),
            new xmldb_field('node_ril100', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'location_type'),
            new xmldb_field('node_name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'node_ril100'),
        ];
        foreach ($sectionfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // railviewer_section_edges: Zuordnung 1..n Kanten -> ein Abschnitt.
        $table = new xmldb_table('railviewer_section_edges');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('edge_id', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('direction', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('von_ril100', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('bis_ril100', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('streckennummer', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('sectionid_fk', XMLDB_KEY_FOREIGN, ['sectionid'], 'railviewer_sections', ['id']);
            $table->add_index('edge_direction_uq', XMLDB_INDEX_UNIQUE, ['edge_id', 'direction']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 202609130002, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130003) {

        // Hin-/Rueckfahrt-Verknuepfung zweier Strecken: rein additiv, bestehende Strecken
        // bleiben mit pairedrouteid/direction = NULL unveraendert (= unverknuepft).
        $table = new xmldb_table('railviewer_routes');

        $pairedfield = new xmldb_field('pairedrouteid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'description');
        if (!$dbman->field_exists($table, $pairedfield)) {
            $dbman->add_field($table, $pairedfield);
        }

        $directionfield = new xmldb_field('direction', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'pairedrouteid');
        if (!$dbman->field_exists($table, $directionfield)) {
            $dbman->add_field($table, $directionfield);
        }

        // FOREIGN_UNIQUE statt Foreign Key + separatem Unique-Index: genau die Kollision, die
        // wir schon einmal gefixt haben (Key kollidiert mit Index auf denselben Feldern), siehe
        // railviewer_ebula_section.sectionid_fk weiter oben in dieser Datei. Unique, weil eine
        // Strecke hoechstens EINE Gegenrichtung haben kann (1:1-Paarung).
        $pairedkey = new xmldb_key('pairedrouteid_fk', XMLDB_KEY_FOREIGN_UNIQUE, ['pairedrouteid'], 'railviewer_routes', ['id']);
        $pairedindex = new xmldb_index('pairedrouteid_fk', XMLDB_INDEX_UNIQUE, ['pairedrouteid']);
        if (!$dbman->index_exists($table, $pairedindex)) {
            $dbman->add_key($table, $pairedkey);
        }

        upgrade_plugin_savepoint(true, 202609130003, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130004) {

        // Explizite Default-Werte für die beiden neuen Wiedergabe-Einstellungen in die DB
        // schreiben, statt sich auf den in admin_setting_config*() deklarierten Default zu
        // verlassen: der wird von Moodle nur als "Standard: ..."-Hinweistext angezeigt, aber
        // erst beim ERSTEN Speichern der Einstellungsseite tatsächlich in die DB übernommen.
        // Bis dahin liefert get_config() false zurück und die Checkbox/Auswahl erscheint
        // fälschlich leer/ungehakt, obwohl der Hinweistext einen anderen Default verspricht -
        // verwirrend für Admins. Nur setzen, falls noch nie ein Wert gespeichert wurde (falls
        // ein Admin zwischenzeitlich schon "aus" gewählt hat, bleibt das unangetastet).
        if (get_config('local_railvieweradmin', 'show_mute_button') === false) {
            set_config('show_mute_button', 1, 'local_railvieweradmin');
        }
        if (get_config('local_railvieweradmin', 'max_playback_speed') === false) {
            set_config('max_playback_speed', '1', 'local_railvieweradmin');
        }

        upgrade_plugin_savepoint(true, 202609130004, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130005) {

        // Einsatzstellen: buendeln mehrere Strecken/Orte (railviewer_routes-Zeilen, inkl. der
        // "Ortskenntnis"-Pseudo-Routen mit nur einem Knoten-Abschnitt) zu einer benannten
        // Gruppe, damit ein Admin bei der Zuweisung nicht jede Strecke einzeln auswaehlen muss.
        $table = new xmldb_table('railviewer_einsatzstellen');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // Zuordnung Einsatzstelle <-> Strecke (n:m, dieselbe Strecke kann zu mehreren
        // Einsatzstellen gehoeren).
        $table = new xmldb_table('railviewer_einsatz_routes');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('einsatzstelleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('routeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('einsatzstelleid_fk', XMLDB_KEY_FOREIGN, ['einsatzstelleid'], 'railviewer_einsatzstellen', ['id']);
            $table->add_key('routeid_fk', XMLDB_KEY_FOREIGN, ['routeid'], 'railviewer_routes', ['id']);
            // Unique auf BEIDEN Feldern zusammen - kollidiert nicht mit den beiden Einzel-FKs
            // oben (die Kollisionsregel greift nur bei exakt demselben Feld-Set, siehe
            // Kommentare bei railviewer_stops/railviewer_ebula_section weiter oben in dieser
            // Datei), verhindert aber, dieselbe Strecke zweimal in dieselbe Einsatzstelle zu
            // haengen.
            $table->add_index('einsatzstelle_route_uq', XMLDB_INDEX_UNIQUE, ['einsatzstelleid', 'routeid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 202609130005, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130006) {

        // "ignite_player_domain" (nur die Domain) durch "ignite_embed_url_template" (das
        // gesamte URL-Muster inkl. Pfad/Parametern, {id}-Platzhalter) ersetzt - siehe
        // settings.php/play.php. Bestehenden Wert übernehmen statt ihn stillschweigend zu
        // verlieren, dann das alte, jetzt ungenutzte Config-Feld aufräumen.
        $olddomain = get_config('local_railvieweradmin', 'ignite_player_domain');
        if ($olddomain && get_config('local_railvieweradmin', 'ignite_embed_url_template') === false) {
            $template = rtrim($olddomain, '/') . '/player/index.html?id={id}&controls=false';
            set_config('ignite_embed_url_template', $template, 'local_railvieweradmin');
        }
        unset_config('ignite_player_domain', 'local_railvieweradmin');

        upgrade_plugin_savepoint(true, 202609130006, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130007) {

        // Produktiv-Fund: railviewer_ebulas.created_at existierte in der Datenbank bereits als
        // DATETIME-Spalte statt "int" wie in install.xml deklariert - vermutlich eine der
        // Tabellen, die ursprünglich direkt in der DB angelegt wurden (siehe Kommentar oben in
        // dieser Datei). field_exists() prüft nur den Feldnamen, nicht den tatsächlichen
        // Spaltentyp, daher blieb der Fehler bislang unentdeckt: ein INSERT mit einem
        // Unix-Timestamp (int, z.B. beim Speichern eines neuen EBuLa-Fahrplans) schlug dann mit
        // "Incorrect datetime value" fehl, weil MySQL die Zahl nicht als DATETIME interpretieren
        // kann.
        //
        // Direkte Typänderung per ALTER ... MODIFY wäre riskant (mit vorhandenen DATETIME-Werten
        // je nach MySQL-Modus entweder ein Fehler oder unbrauchbare Zahlen) - stattdessen: alte
        // Spalte umbenennen, neue int-Spalte anlegen, Werte per UNIX_TIMESTAMP() übernehmen,
        // alte Spalte entfernen. Nur für MySQL/MariaDB behandelt (dort beobachtet und die
        // Syntax getestet) - andere DB-Familien werden nicht automatisch angefasst, um kein
        // ungetestetes Risiko auf einer strukturell anderen Datenbank einzugehen.
        $ebulastable = new xmldb_table('railviewer_ebulas');
        if ($DB->get_dbfamily() === 'mysql' && $dbman->table_exists($ebulastable)) {
            $columns = $DB->get_columns('railviewer_ebulas');
            if (isset($columns['created_at']) && $columns['created_at']->meta_type !== 'I') {
                $DB->execute("ALTER TABLE {railviewer_ebulas} CHANGE COLUMN created_at created_at_old DATETIME NULL DEFAULT NULL");
                $DB->execute("ALTER TABLE {railviewer_ebulas} ADD COLUMN created_at BIGINT(10) NOT NULL DEFAULT 0");
                $DB->execute("UPDATE {railviewer_ebulas}
                                 SET created_at = CASE
                                     WHEN created_at_old IS NULL THEN 0
                                     ELSE UNIX_TIMESTAMP(created_at_old)
                                 END");
                $DB->execute("ALTER TABLE {railviewer_ebulas} DROP COLUMN created_at_old");
            }
        }

        upgrade_plugin_savepoint(true, 202609130007, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609130008) {

        // railviewer_ebula_section speicherte bisher keine Referenz zurück auf den
        // ursprünglichen Roh-Fahrplan (railviewer_ebulas), aus dem der jeweils einem Abschnitt
        // zugewiesene Ausschnitt stammt - in den Listen (ebulas.php/index_ebulas.php) war daher
        // nicht erkennbar, welcher Abschnitt zu welchem hochgeladenen Fahrplan gehört. Additiv,
        // nullable - bereits vor dieser Änderung gespeicherte Zuweisungen bleiben ohne bekannte
        // Herkunft (kann nicht zuverlässig nachträglich ermittelt werden).
        $table = new xmldb_table('railviewer_ebula_section');
        $field = new xmldb_field('ebulaid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'sectionid');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 202609130008, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202609160009) {

        // SharePoint als vierte Videoquelle (neben Upload/Vimeo/ignite.video). Anders als bei
        // Vimeo/ignite reicht eine einzelne Anbieter-ID nicht aus: Microsoft Graph identifiziert
        // eine Datei erst durch die Kombination aus driveId (Bibliothek) UND itemId (Datei
        // innerhalb dieser Bibliothek) eindeutig. Additiv, nullable - bestehende Abschnitte
        // anderer Quellen bleiben unberührt.
        $table = new xmldb_table('railviewer_sections');

        $field = new xmldb_field('sharepoint_drive_id', XMLDB_TYPE_CHAR, '128', null, null, null, null, 'ignite_video_id');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sharepoint_item_id', XMLDB_TYPE_CHAR, '128', null, null, null, null, 'sharepoint_drive_id');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 202609160009, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202610080010) {
        // S3 Object Storage als weitere Videoquelle. Additiv, nullable.
        $table = new xmldb_table('railviewer_sections');
        $field = new xmldb_field('s3_object_key', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 202610080010, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202610090011) {
        // Neue Einzelrechte (db/access.php) ersetzen local/railvieweradmin:manage, neue Events
        // (db/events.php), Webservice (db/services.php) und Webhook-Einstellungen. Kein
        // Schema-Update nötig - Moodle liest Rechte/Events/Services beim Savepoint neu ein.
        upgrade_plugin_savepoint(true, 202610090011, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202610100012) {
        // Lernmethoden-Feature: neue Tabellen aus install.xml (nur falls noch nicht vorhanden)
        // und neue Felder an railviewer_assignments. Alles additiv.
        $newtables = ['railviewer_methods', 'railviewer_experience_types', 'railviewer_route_reqs',
            'railviewer_assignment_req', 'railviewer_rides', 'railviewer_ride_sections',
            'railviewer_section_durations', 'railviewer_route_substs', 'railviewer_assignment_subst',
            'railviewer_user_einsatz', 'railviewer_compliance_mail', 'railviewer_declarations'];
        foreach ($newtables as $tablename) {
            if (!$dbman->table_exists($tablename)) {
                $dbman->install_one_table_from_xmldb_file(__DIR__ . '/install.xml', $tablename);
            }
        }

        $table = new xmldb_table('railviewer_assignments');
        $assignmentfields = [
            new xmldb_field('experiencetypeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'lastviewed'),
            new xmldb_field('waived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'experiencetypeid'),
            new xmldb_field('waivereason', XMLDB_TYPE_TEXT, null, null, null, null, null, 'waived'),
            new xmldb_field('waivedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'waivereason'),
            new xmldb_field('waivedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'waivedby'),
            new xmldb_field('completedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'waivedtime'),
        ];
        foreach ($assignmentfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        \local_railvieweradmin\learning::seed_defaults();
        upgrade_plugin_savepoint(true, 202610100012, 'local', 'railvieweradmin');
    }

    if ($oldversion < 202610110013) {
        // CSV-Import: optionaler Schlüssel an Abschnitten, Strecken und Einsatzstellen.
        foreach (['railviewer_sections', 'railviewer_routes', 'railviewer_einsatzstellen'] as $tablename) {
            $table = new xmldb_table($tablename);
            $field = new xmldb_field('importkey', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 202610110013, 'local', 'railvieweradmin');
    }

    return true;
}
