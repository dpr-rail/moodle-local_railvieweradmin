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
 * Cache-Busting-URL für eine statische Datei dieses Plugins (CSS/JS), anhand ihres
 * Datei-Zeitstempels. $PAGE->requires->css()/eine reine <link>/<script>-URL ohne diesen
 * Parameter wird vom BROWSER unter derselben URL zwischengespeichert - ein serverseitiges
 * "Caches leeren" in Moodle hat darauf keinen Einfluss, nur ein hartes Neuladen im Browser
 * würde helfen. Mit ?v=<mtime> ändert sich die URL bei jeder Dateiänderung automatisch, der
 * Browser holt die Datei dann zuverlässig neu (gleiches Muster wie bereits bei den vendorten
 * Streckenkarten-Dateien in streckenmap.php/map.php verwendet).
 *
 * @param string $relpath z.B. '/styles.css', relativ zum Plugin-Wurzelverzeichnis
 * @return moodle_url
 */
function local_railvieweradmin_asset_url($relpath) {
    $fullpath = __DIR__ . $relpath;
    $rev = is_file($fullpath) ? filemtime($fullpath) : 0;
    return new moodle_url('/local/railvieweradmin' . $relpath, ['v' => $rev]);
}

/**
 * Prüft, ob ein Nutzer auf die Video-/Overlay-Daten eines Streckenabschnitts zugreifen darf.
 *
 * Erlaubt ist der Zugriff für Railviewer-Admins (local/railvieweradmin:editsections) sowie für Nutzer, denen eine
 * Strecke zugewiesen wurde, die diesen Abschnitt enthält. Damit kann pluginfile.php-Zugriff
 * nicht mehr von jedem eingeloggten Moodle-Nutzer missbraucht werden, um beliebige
 * Streckenvideos abzurufen, ohne dass ihm die zugehörige Strecke zugewiesen wurde.
 *
 * @param int $sectionid ID aus railviewer_sections (entspricht dem itemid der Datei-Area)
 * @param int $userid
 * @return bool
 */
function local_railvieweradmin_user_can_access_section($sectionid, $userid) {
    global $DB;

    if (has_capability('local/railvieweradmin:editsections', context_system::instance())) {
        return true;
    }

    $sql = "SELECT 1
              FROM {railviewer_route_sections} rrs
              JOIN {railviewer_assignments} ra ON ra.routeid = rrs.routeid
             WHERE rrs.sectionid = :sectionid AND ra.userid = :userid";

    return $DB->record_exists_sql($sql, ['sectionid' => $sectionid, 'userid' => $userid]);
}

/**
 * Löst einen Sprachstring auf, der je nach Admin-Einstellung "Anrede" (Du/Sie) in zwei
 * Varianten existieren kann. Gesucht wird zuerst "{$identifier}_du" bzw. "{$identifier}_sie",
 * je nach eingestelltem Modus; existiert diese Variante nicht, wird der neutrale Basisstring
 * "{$identifier}" verwendet. So müssen nur Strings mit tatsächlicher persönlicher Anrede
 * doppelt gepflegt werden.
 *
 * Wird sowohl von local_railvieweradmin als auch (cross-plugin) von mod_railviewer genutzt,
 * das ohnehin hart von diesem Plugin abhängt.
 *
 * @param string $identifier Basis-Sprachstring-Key
 * @param string $component Sprachdatei-Komponente des Strings (nicht der Einstellung)
 * @param mixed $a Platzhalter-Daten für get_string()
 * @return string
 */
function local_railvieweradmin_get_address_string($identifier, $component = 'local_railvieweradmin', $a = null) {
    $mode = get_config('local_railvieweradmin', 'addressing');
    $suffix = ($mode === 'du') ? 'du' : 'sie';
    $suffixed = $identifier . '_' . $suffix;

    if (get_string_manager()->string_exists($suffixed, $component)) {
        return get_string($suffixed, $component, $a);
    }

    return get_string($identifier, $component, $a);
}

/**
 * File serving callback for pluginfile.php.
 */
function local_railvieweradmin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER;

    require_login();

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea === 'ridesignature') {
        // Unterschrift einer Lernfahrt: nur Fahrender, benannter Ausbilder und Berechtigte.
        $rideid = (int) array_shift($args);
        $ride = $GLOBALS['DB']->get_record('railviewer_rides', ['id' => $rideid]);
        $allowed = $ride && ((int) $ride->userid === (int) $USER->id || (int) $ride->trainerid === (int) $USER->id
            || has_capability('local/railvieweradmin:viewprogress', $context)
            || has_capability('local/railvieweradmin:assignroutes', $context));
        if (!$allowed) {
            send_file_not_found();
        }
        $file = get_file_storage()->get_file($context->id, 'local_railvieweradmin', 'ridesignature', $rideid, '/', 'signature.png');
        if (!$file) {
            send_file_not_found();
        }
        send_stored_file($file, 0, 0, true, $options);
    }

    if ($filearea !== 'sectionvideo') {
        send_file_not_found();
    }

    if (count($args) < 1) {
        send_file_not_found();
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';
    if ($filepath === '//') {
        $filepath = '/';
    }

    if (!local_railvieweradmin_user_can_access_section($itemid, $USER->id)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_railvieweradmin', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Rendert eine kleine Vorschau des aktuell mit einer Sektion verknüpften Videos, quellen-
 * unabhängig (Upload/Vimeo/ignite.video). Bisher gab es eine solche Vorschau nur auf der
 * Overlay-Bearbeitungsseite (edit_overlay.php) - beim reinen Bearbeiten der Sektion selbst
 * (edit_section.php) war überhaupt nicht erkennbar, welches Video schon hinterlegt ist.
 *
 * @param stdClass $section Datensatz aus railviewer_sections
 * @param context $context
 * @return string HTML
 */
function local_railvieweradmin_render_video_preview($section, $context) {
    $video_source = $section->video_source ?? null;
    if (empty($video_source)) {
        if (!empty($section->vimeo_video_id)) {
            $video_source = 'vimeo';
        } else if (!empty($section->ignite_video_id)) {
            $video_source = 'ignite';
        } else if (!empty($section->sharepoint_item_id)) {
            $video_source = 'sharepoint';
        } else {
            $video_source = 'upload';
        }
    }

    $out = '';

    if ($video_source === 'vimeo' && !empty($section->vimeo_video_id)) {
        $out .= html_writer::tag('h4', get_string('current_video', 'local_railvieweradmin') . ' (Vimeo)');
        $out .= html_writer::tag('iframe', '', [
            'src' => 'https://player.vimeo.com/video/' . (int) $section->vimeo_video_id . '?api=1',
            'width' => 480,
            'height' => 270,
            'frameborder' => 0,
            'allow' => 'autoplay; fullscreen; picture-in-picture',
            'allowfullscreen' => true,
        ]);
    } else if ($video_source === 'ignite' && !empty($section->ignite_video_id)) {
        $out .= html_writer::tag('h4', get_string('current_video', 'local_railvieweradmin') . ' (ignite.video)');

        // Solange ignite.video ein Video noch kodiert (status PROGRESSING/REPLACING), zeigt der
        // Embed-Player nur "Dieses Video ist nicht verfügbar" - ohne erkennbaren Grund für den
        // Admin. Status/Fortschritt (statusProgress, 0-100) sind laut
        // docs.ignite.video/api-reference/videos/object genau für diesen Fall vorgesehen ("poll
        // ... to show progress in your UI").
        require_once(__DIR__ . '/classes/ignite_api.php');
        $api = new \local_railvieweradmin\ignite_api();
        $video = $api->get_video($section->ignite_video_id);
        $status = $video['status'] ?? null;

        if ($status === 'PROGRESSING' || $status === 'REPLACING') {
            $progress = isset($video['statusProgress']) ? (int) $video['statusProgress'] : 0;
            $out .= local_railvieweradmin_render_ignite_progress($section->ignite_video_id, $progress);
        } else if ($status === 'ERROR') {
            $errormessage = $video['errorMessage'] ?? '';
            $out .= html_writer::div(
                get_string('ignite_encoding_error', 'local_railvieweradmin', s($errormessage)),
                'alert alert-danger'
            );
        } else {
            // COMPLETE, oder Status unbekannt (z.B. API-Key nicht (mehr) gültig/API nicht
            // erreichbar) - wie bisher versuchen, das Video einzubetten.
            // Ganzes URL-Muster (nicht nur die Domain) konfigurierbar, mit {id}-Platzhalter -
            // siehe settings.php. "ignite_player_domain" wurde durch "ignite_embed_url_template"
            // ersetzt und per Upgrade-Schritt entfernt (siehe db/upgrade.php) - derselbe
            // Fallback-Wert wie in mod_railviewer/play.php, damit beide Stellen konsistent
            // bleiben.
            $ignite_embed_url_template = get_config('local_railvieweradmin', 'ignite_embed_url_template')
                ?: 'https://play.ignite.video/player/index.html?id={id}&controls=false';
            $out .= html_writer::tag('iframe', '', [
                'src' => str_replace('{id}', urlencode($section->ignite_video_id), $ignite_embed_url_template),
                'width' => 480,
                'height' => 270,
                'frameborder' => 0,
                'allow' => 'autoplay; fullscreen; picture-in-picture',
                'allowfullscreen' => true,
            ]);
        }
    } else if ($video_source === 'sharepoint' && !empty($section->sharepoint_item_id)) {
        // Kein iframe (siehe Kommentar in classes/sharepoint_api.php, warum es dafür keine
        // steuerbare Player-API gibt) - stattdessen ein ganz normaler nativer <video>-Tag, der
        // die Datei über sharepoint_stream.php bezieht (Graph-Downloadadresse wird dort bei
        // jedem Aufruf frisch aufgelöst, siehe dortiger Kommentar zur zeitlichen Begrenzung).
        $out .= html_writer::tag('h4', get_string('current_video', 'local_railvieweradmin') . ' (SharePoint)');
        $streamurl = new moodle_url('/local/railvieweradmin/sharepoint_stream.php', ['sectionid' => $section->id]);
        $out .= html_writer::tag('video', html_writer::tag('source', '', ['src' => $streamurl->out(false)])
                . local_railvieweradmin_get_address_string('browser_no_video_support'),
            ['width' => 480, 'controls' => true]);
    } else if ($video_source === 'upload') {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_railvieweradmin', 'sectionvideo', $section->id, 'sortorder', false);
        if ($files) {
            $file = reset($files);
            $fileurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            $out .= html_writer::tag('h4', get_string('current_video', 'local_railvieweradmin'));
            $out .= html_writer::tag('video', html_writer::tag('source', '', ['src' => $fileurl->out(false), 'type' => $file->get_mimetype()])
                    . local_railvieweradmin_get_address_string('browser_no_video_support'),
                ['width' => 480, 'controls' => true]);
        } else {
            $out .= html_writer::div(get_string('no_video_found_for_section', 'local_railvieweradmin'), 'alert alert-warning');
        }
    } else {
        $out .= html_writer::div(get_string('no_video_found_for_section', 'local_railvieweradmin'), 'alert alert-warning');
    }

    return html_writer::div($out, 'railviewer-section-video-preview mb-3');
}

/**
 * Rendert einen Kodier-Fortschrittsbalken für ein noch verarbeitendes ignite.video-Video
 * (status PROGRESSING/REPLACING) und pollt per JS alle paar Sekunden den aktuellen Fortschritt
 * nach (siehe ajax/get_ignite_video_status.php) - ignite.video empfiehlt dieses Muster selbst
 * in ihrer API-Doku ("poll ... to show progress in your UI"). Sobald die Kodierung
 * abgeschlossen ist (oder fehlschlägt), lädt die Seite neu und zeigt dann den echten
 * Embed-Player bzw. die Fehlermeldung.
 *
 * @param string $videoid
 * @param int $progress 0-100
 * @return string HTML
 */
function local_railvieweradmin_render_ignite_progress($videoid, $progress) {
    global $PAGE;

    $progress = max(0, min(100, $progress));
    $label = get_string('ignite_encoding_label', 'local_railvieweradmin');

    $out = html_writer::start_div('railviewer-encoding-status');
    $out .= html_writer::tag('p', $label . ' ' . $progress . '%', ['id' => 'ignite-encoding-label']);
    $out .= html_writer::start_tag('div', ['class' => 'railviewer-upload-progress']);
    $out .= html_writer::div('', 'railviewer-upload-progress-bar', ['id' => 'ignite-encoding-bar', 'style' => 'width:' . $progress . '%;']);
    $out .= html_writer::end_tag('div');
    $out .= html_writer::tag('p', get_string('ignite_encoding_wait_notice', 'local_railvieweradmin'), ['class' => 'text-muted small']);
    $out .= html_writer::end_div();

    $ajaxurl = (new moodle_url('/local/railvieweradmin/ajax/get_ignite_video_status.php'))->out(false);
    $js = "
    (function() {
        var videoId = " . json_encode($videoid) . ";
        var ajaxUrl = " . json_encode($ajaxurl) . ";
        var labelPrefix = " . json_encode($label) . ";
        var labelEl = document.getElementById('ignite-encoding-label');
        var barEl = document.getElementById('ignite-encoding-bar');

        function poll() {
            fetch(ajaxUrl + '?id=' + encodeURIComponent(videoId), {credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || !data.success) {
                        setTimeout(poll, 8000);
                        return;
                    }
                    if (data.status === 'COMPLETE' || data.status === 'ERROR') {
                        window.location.reload();
                        return;
                    }
                    var pct = data.statusProgress || 0;
                    if (labelEl) { labelEl.textContent = labelPrefix + ' ' + pct + '%'; }
                    if (barEl) { barEl.style.width = pct + '%'; }
                    setTimeout(poll, 5000);
                })
                .catch(function() { setTimeout(poll, 8000); });
        }
        setTimeout(poll, 5000);
    })();
    ";
    $PAGE->requires->js_init_code($js);

    return $out;
}

/**
 * Ermittelt, welche der unterstützten Videoquellen aktuell in den Plugin-Settings eingerichtet
 * sind (also tatsächlich benutzbar sind, ohne sofort mit einer "keine Zugangsdaten"-Fehlermeldung
 * zu enden). "upload" (Moodles eigener Dateispeicher) braucht zwar keine externen Zugangsdaten,
 * ist aber ebenfalls über einen eigenen An/Aus-Schalter (enable_upload_source) deaktivierbar -
 * im Regelfall richtet ein Admin nur EINE Videoquelle ein.
 *
 * Genutzt von section_form.php, um beim Anlegen/Bearbeiten eines Abschnitts nur die Quellen zur
 * Auswahl anzubieten, die der Admin auch wirklich eingerichtet hat - eine anwählbare, aber gar
 * nicht funktionsfähige Option verwirrt nur unnötig.
 *
 * @return array<string,bool> Schlüssel = video_source-Wert, Wert = ob konfiguriert
 */
function local_railvieweradmin_get_configured_video_sources() {
    // "Upload" (Moodles eigener Dateispeicher) braucht zwar keine externen Zugangsdaten, ist
    // aber trotzdem ein eigener An/Aus-Schalter (enable_upload_source) - im Regelfall richtet
    // ein Admin nur EINE Videoquelle ein, "Upload" soll dann nicht als scheinbare Zusatzoption
    // auftauchen. get_config() liefert false, solange die Einstellung noch nie gespeichert
    // wurde (Standardwert "1" wird erst beim ersten Öffnen/Speichern der Settings-Seite in die
    // DB geschrieben) - ohne diese Sonderbehandlung wäre Upload direkt nach dem Update dieses
    // Plugins fälschlich ausgeblendet (gleiches, bereits etabliertes Muster wie bei
    // "show_mute_button" in play.php).
    $uploadenabledraw = get_config('local_railvieweradmin', 'enable_upload_source');
    $uploadconfigured = $uploadenabledraw === false ? true : (bool) $uploadenabledraw;

    $vimeoconfigured = trim((string) get_config('local_railvieweradmin', 'vimeo_access_token')) !== '';
    $igniteconfigured = trim((string) get_config('local_railvieweradmin', 'ignite_api_key')) !== '';
    // SharePoint braucht alle fünf Angaben zusammen (App-Registrierung + Zielort in der
    // Bibliothek) - fehlt auch nur eine, kann sharepoint_api sich gar nicht authentifizieren
    // bzw. den Zielordner nicht finden (siehe sharepoint_api::__construct()).
    $sharepointconfigured = trim((string) get_config('local_railvieweradmin', 'sharepoint_tenant_id')) !== ''
        && trim((string) get_config('local_railvieweradmin', 'sharepoint_client_id')) !== ''
        && trim((string) get_config('local_railvieweradmin', 'sharepoint_client_secret')) !== ''
        && trim((string) get_config('local_railvieweradmin', 'sharepoint_site_url')) !== ''
        && trim((string) get_config('local_railvieweradmin', 'sharepoint_library_name')) !== '';

    $s3configured = trim((string) get_config('local_railvieweradmin', 's3_endpoint')) !== ''
        && trim((string) get_config('local_railvieweradmin', 's3_bucket')) !== ''
        && trim((string) get_config('local_railvieweradmin', 's3_access_key')) !== ''
        && trim((string) get_config('local_railvieweradmin', 's3_secret_key')) !== '';

    return [
        'upload' => $uploadconfigured,
        'vimeo' => $vimeoconfigured,
        'ignite' => $igniteconfigured,
        'sharepoint' => $sharepointconfigured,
        's3' => $s3configured,
    ];
}

/**
 * Verknüpft (oder löst) zwei Strecken symmetrisch als Hin-/Rückfahrt-Paar derselben
 * physischen Strecke. Eine Strecke kann höchstens einen Partner haben (1:1, siehe
 * pairedrouteid_fk-Key in db/install.xml). Beide Seiten werden immer zusammen aktualisiert -
 * ein inkonsistentes hin/hin- oder rueck/rueck-Paar ist dadurch strukturell ausgeschlossen,
 * die Gegenseite bekommt automatisch die jeweils andere Richtung.
 *
 * @param int $routeid Strecke, deren Verknüpfung geändert wird
 * @param int $newpartnerid Neue Gegenrichtungs-Strecke, oder 0 um die Verknüpfung zu lösen
 * @param string|null $direction 'hin'/'rueck' - die Richtung von $routeid selbst.
 *   Wird ignoriert, wenn $newpartnerid == 0.
 */
function local_railvieweradmin_update_route_pairing($routeid, $newpartnerid, $direction) {
    global $DB;
    $transaction = $DB->start_delegated_transaction();

    $route = $DB->get_record('railviewer_routes', ['id' => $routeid], '*', MUST_EXIST);

    // Eine ggf. bestehende alte Verknüpfung immer zuerst lösen (auch wenn sich der Partner
    // ändert oder ganz entfernt wird) - der alte Partner wird dadurch zu einer normalen,
    // unverknüpften Strecke, nie mitgelöscht.
    if (!empty($route->pairedrouteid) && $route->pairedrouteid != $newpartnerid) {
        $DB->set_field('railviewer_routes', 'pairedrouteid', null, ['id' => $route->pairedrouteid]);
        $DB->set_field('railviewer_routes', 'direction', null, ['id' => $route->pairedrouteid]);
    }

    if (empty($newpartnerid)) {
        $DB->set_field('railviewer_routes', 'pairedrouteid', null, ['id' => $routeid]);
        $DB->set_field('railviewer_routes', 'direction', null, ['id' => $routeid]);
        $transaction->allow_commit();
        return;
    }

    // Verteidigung gegen Race Conditions (zwei Admins speichern gleichzeitig): falls der
    // gewünschte neue Partner seinerseits noch an eine dritte Strecke gebunden ist, diese
    // Verknüpfung ebenfalls sauber lösen - sonst würde der UNIQUE-Key das als DB-Fehler
    // ablehnen statt es aufzulösen.
    $partner = $DB->get_record('railviewer_routes', ['id' => $newpartnerid], '*', MUST_EXIST);
    if (!empty($partner->pairedrouteid) && $partner->pairedrouteid != $routeid) {
        $DB->set_field('railviewer_routes', 'pairedrouteid', null, ['id' => $partner->pairedrouteid]);
        $DB->set_field('railviewer_routes', 'direction', null, ['id' => $partner->pairedrouteid]);
    }

    $opposite = ($direction === 'rueck') ? 'hin' : 'rueck';
    $DB->set_field('railviewer_routes', 'pairedrouteid', $newpartnerid, ['id' => $routeid]);
    $DB->set_field('railviewer_routes', 'direction', $direction, ['id' => $routeid]);
    $DB->set_field('railviewer_routes', 'pairedrouteid', $routeid, ['id' => $newpartnerid]);
    $DB->set_field('railviewer_routes', 'direction', $opposite, ['id' => $newpartnerid]);

    $transaction->allow_commit();
}

/**
 * Gruppiert eine Liste von Objekten/Arrays (je mit routeid und pairedrouteid) so, dass
 * Hin-/Rückfahrt-Paare, die BEIDE in der übergebenen Liste vorhanden sind, zusammengefasst
 * werden. Fehlt die Gegenrichtung in der Liste (z.B. ein Nutzer hat bisher nur eine Richtung
 * zugewiesen bekommen), bleibt der Eintrag ein normales Einzelelement - die Paarung auf
 * Strecken-Ebene erzwingt also nirgends, dass auch tatsächlich beide Richtungen zugewiesen
 * sein müssen.
 *
 * Wiederverwendet von: local_railvieweradmin/assignments.php, mod_railviewer/index.php,
 * mod_railviewer/view.php.
 *
 * @param array $items Liste von stdClass/Array-Elementen mit routeid/pairedrouteid (gemischt
 *   erlaubt, wird über array- bzw. Objekt-Zugriff gelesen je nach Typ des jeweiligen Elements)
 * @return array Liste von ['type' => 'single', 'item' => $x] bzw.
 *   ['type' => 'pair', 'items' => [$hin, $rueck]]
 */
function local_railvieweradmin_group_by_route_pairing(array $items) {
    $get = function ($item, $field) {
        return is_array($item) ? ($item[$field] ?? null) : ($item->$field ?? null);
    };

    $byrouteid = [];
    foreach ($items as $item) {
        $byrouteid[$get($item, 'routeid')] = $item;
    }

    $consumed = [];
    $groups = [];
    foreach ($items as $item) {
        $routeid = $get($item, 'routeid');
        if (in_array($routeid, $consumed, true)) {
            continue;
        }

        $pairedrouteid = $get($item, 'pairedrouteid');
        $partner = (!empty($pairedrouteid) && isset($byrouteid[$pairedrouteid])) ? $byrouteid[$pairedrouteid] : null;

        if ($partner) {
            // Konsistente Reihenfolge [hin, rueck] statt "wie in $items zuerst aufgetaucht".
            $hin = ($get($item, 'direction') === 'hin') ? $item : $partner;
            $rueck = ($hin === $item) ? $partner : $item;
            $groups[] = ['type' => 'pair', 'items' => [$hin, $rueck]];
            $consumed[] = $routeid;
            $consumed[] = $get($partner, 'routeid');
        } else {
            $groups[] = ['type' => 'single', 'item' => $item];
            $consumed[] = $routeid;
        }
    }

    return $groups;
}

/**
 * Löschen-Link, der nur angezeigt wird, wenn der Nutzer das Recht local/railvieweradmin:deletesections
 * hat. Die serverseitige Prüfung erfolgt zusätzlich im jeweiligen Lösch-Zweig.
 *
 * @param moodle_url $url
 * @param array $attributes HTML-Attribute des Links (z.B. onclick-Bestätigung)
 * @param string $prefix Trenner, der nur bei sichtbarem Link vorangestellt wird
 * @return string HTML, leer ohne Berechtigung
 */
function local_railvieweradmin_delete_link(moodle_url $url, array $attributes = [], $prefix = ' | ') {
    if (!has_capability('local/railvieweradmin:deletesections', context_system::instance())) {
        return '';
    }
    return $prefix . html_writer::link($url, get_string('delete', 'local_railvieweradmin'), $attributes);
}

/**
 * Gleicht completed_views einer Zuweisung mit den tatsächlich gespeicherten Durchlauf-Markern
 * (railviewer_views.sectionid = -1) ab und löst bei Erreichen der geforderten Anzahl die Events
 * aus:
 *  - \local_railvieweradmin\event\route_completed, sobald diese Zuweisung erstmals vollständig ist,
 *  - \local_railvieweradmin\event\all_routes_completed, sobald damit ALLE Zuweisungen des Nutzers
 *    vollständig sind.
 * Idempotent: Events werden nur beim Überschreiten der Schwelle ausgelöst, nicht bei jedem Aufruf.
 *
 * @param int $assignmentid
 * @return stdClass|null aktualisierte Zuweisung (null, wenn sie nicht existiert)
 */
function local_railvieweradmin_sync_assignment_progress($assignmentid) {
    global $DB;

    $assignment = $DB->get_record('railviewer_assignments', ['id' => $assignmentid]);
    if (!$assignment) {
        return null;
    }

    $actual = $DB->count_records('railviewer_views', [
        'assignmentid' => $assignment->id,
        'userid' => $assignment->userid,
        'sectionid' => -1,
    ]);

    $learning = \local_railvieweradmin\learning::enabled();
    // Ohne Lernmethoden-Feature entscheiden allein die Zähler (bisheriges Verhalten); mit Feature
    // zählt die Gesamtvollständigkeit aller Abschnitte x Methoden, der Zustand steht in completedtime.
    $wascomplete = $learning ? !empty($assignment->completedtime)
        : $assignment->completed_views >= $assignment->required_views;
    if ((int) $assignment->completed_views !== $actual) {
        $assignment->completed_views = $actual;
        $assignment->lastviewed = time();
        $DB->update_record('railviewer_assignments', $assignment);
    }
    $iscomplete = \local_railvieweradmin\learning::is_complete($assignment);
    if ($learning && $iscomplete !== $wascomplete) {
        $assignment->completedtime = $iscomplete ? time() : null;
        $DB->update_record('railviewer_assignments', $assignment);
    }

    if ($iscomplete && !$wascomplete) {
        \local_railvieweradmin\event\route_completed::create([
            'context' => context_system::instance(),
            'objectid' => $assignment->id,
            'relateduserid' => $assignment->userid,
            'other' => [
                'routeid' => (int) $assignment->routeid,
                'requiredviews' => (int) $assignment->required_views,
                'completedviews' => (int) $assignment->completed_views,
            ],
        ])->trigger();

        if ($learning) {
            $open = 0;
            foreach ($DB->get_records('railviewer_assignments', ['userid' => $assignment->userid]) as $other) {
                if (!\local_railvieweradmin\learning::is_complete($other)) {
                    $open++;
                }
            }
        } else {
            $open = $DB->count_records_select('railviewer_assignments',
                'userid = :userid AND completed_views < required_views', ['userid' => $assignment->userid]);
        }
        // Mit Abschlusserklärung feuert all_routes_completed erst, wenn der Lernende sie bestätigt
        // (siehe learning::answer_declaration()).
        if ($open === 0 && !\local_railvieweradmin\learning::declaration_required()) {
            \local_railvieweradmin\event\all_routes_completed::create([
                'context' => context_system::instance(),
                'relateduserid' => $assignment->userid,
                'other' => [
                    'routecount' => $DB->count_records('railviewer_assignments', ['userid' => $assignment->userid]),
                ],
            ])->trigger();
        }
    }

    return $assignment;
}

/**
 * Bereinigt eine frei eingegebene Bezeichnung (Lernmethode, Berufserfahrungstyp). PARAM_TEXT
 * würde bei "<= 12 Monate" alles ab dem "<" als HTML-Tag entfernen (Name wäre danach leer), und
 * format_string() entfernt Tags beim Anzeigen ebenfalls. Deshalb werden Vergleichszeichen vor
 * dem Speichern durch gleichwertige, HTML-unkritische Zeichen ersetzt ("<=" -> "≤", ">=" -> "≥").
 *
 * @param string $raw Rohwert aus PARAM_RAW
 * @return string
 */
function local_railvieweradmin_clean_label($raw) {
    $label = trim((string) $raw);
    $label = str_replace(['<=', '>='], ['≤', '≥'], $label);
    $label = str_replace(['<', '>'], ['‹', '›'], $label);
    $label = preg_replace('/[\x00-\x1F\x7F]/u', '', $label);
    return trim(\core_text::substr($label, 0, 255));
}

/**
 * Navigation (Seitenleiste/Dashboard-Navigation): Eintrag "Lernfahrten" für Nutzer mit Zuweisungen oder
 * offenen Bestätigungen; bei offenen Bestätigungen mit Anzahl, damit Ausbilder sie sofort finden.
 *
 * @param global_navigation $navigation
 */
function local_railvieweradmin_extend_navigation(global_navigation $navigation) {
    global $USER, $DB;
    if (!isloggedin() || isguestuser() || !\local_railvieweradmin\learning::enabled()) {
        return;
    }
    $pending = count(\local_railvieweradmin\learning::pending_confirmations((int) $USER->id));
    if (!$pending && !$DB->record_exists('railviewer_assignments', ['userid' => $USER->id])) {
        return;
    }
    $title = get_string('my_rides', 'local_railvieweradmin') . ($pending ? ' (' . $pending . ')' : '');
    $navigation->add($title, new moodle_url('/mod/railviewer/rides.php'), navigation_node::TYPE_CUSTOM, null,
        'railviewer_rides', new pix_icon('i/checkpermissions', ''));
}
