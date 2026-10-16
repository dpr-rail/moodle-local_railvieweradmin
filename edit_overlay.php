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

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/railvieweradmin:editsections', $context);

$id = optional_param('id', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

global $DB;

$defaultvalues = [
    'fontsize'       => get_config('local_railvieweradmin', 'overlay_default_fontsize'),
    'fontcolor'      => get_config('local_railvieweradmin', 'overlay_default_fontcolor'),
    'backgroundcolor'=> get_config('local_railvieweradmin', 'overlay_default_backgroundcolor'),
    'bordercolor'    => get_config('local_railvieweradmin', 'overlay_default_bordercolor'),
    'markerposition'  => get_config('local_railvieweradmin', 'overlay_default_markerposition'),
];

// Overlay-Daten vorbereiten
if ($id) {
    $overlay = $DB->get_record('railviewer_overlays', ['id' => $id], '*', MUST_EXIST);
    $sectionid = $overlay->sectionid;
} else {
    // Ermittle den höchsten endtime für diese sectionid
    $maxendtime = $DB->get_field_sql(
        "SELECT MAX(endtime) FROM {railviewer_overlays} WHERE sectionid = :sectionid",
        ['sectionid' => $sectionid]
    );

    // Falls kein Overlay existiert, starte bei 0
    $starttime = is_null($maxendtime) ? 0 : (int)$maxendtime;

    $overlay = new stdClass();
    $overlay->id = 0;
    $overlay->sectionid = $sectionid;
    $overlay->name = '';
    $overlay->starttime = $starttime;
    $overlay->endtime = $starttime + 1;
    $overlay->posx = 0;
    $overlay->posy = 0;
    $overlay->fontsize = $defaultvalues['fontsize'];
    $overlay->fontcolor = $defaultvalues['fontcolor'];
    $overlay->backgroundcolor = $defaultvalues['backgroundcolor'];
    $overlay->bordercolor = $defaultvalues['bordercolor'];
    $overlay->markerposition = $defaultvalues['markerposition'];
    $overlay->fixedboxwidth = 0;
}

$mform = new \local_railvieweradmin\forms\overlay_form(null, ['defaults' => $defaultvalues]);

// WICHTIG: AJAX-Behandlung MUSS vor allen HTML-Ausgaben stehen!
if (optional_param('ajax', 0, PARAM_INT) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if ($mform->is_submitted() && $data = $mform->get_data()) {
            if ($data->id) {
                $DB->update_record('railviewer_overlays', $data);
                $message = get_string('overlay_updated', 'local_railvieweradmin');
            } else {
                unset($data->id);
                $DB->insert_record('railviewer_overlays', $data);
                $message = get_string('overlay_created', 'local_railvieweradmin');
            }

            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => get_string('invalid_data', 'local_railvieweradmin')]);
        }
    } catch (Exception $e) {
        debugging('edit_overlay.php: ' . $e->getMessage(), DEBUG_NORMAL);
        echo json_encode(['success' => false, 'message' => get_string('generic_ajax_error', 'local_railvieweradmin')]);
    }
    exit; // Hier beenden - keine weitere HTML-Ausgabe!
}

// Ab hier beginnt die normale HTML-Seiten-Ausgabe

$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_overlay.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_overlay', 'local_railvieweradmin') : get_string('create_new_overlay', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('edit_overlay', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('overlay_sections_title', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_overlays.php'));
$PAGE->navbar->add($id ? get_string('edit_overlay', 'local_railvieweradmin') : get_string('create_new_overlay', 'local_railvieweradmin'));

echo $OUTPUT->header();
$PAGE->requires->js_init_code("
    require(['jquery'], function($) {

        // Farbfelder und Picker synchronisieren
        ['fontcolor', 'backgroundcolor', 'bordercolor'].forEach(function(id) {
            const textfield = $('#id_' + id);
            const colorpicker = $('<input>', {
                type: 'color',
                id: id + 'picker',
                css: { marginLeft: '8px', verticalAlign: 'middle' }
            });

            // Picker -> Textfeld
            colorpicker.on('input', function () {
                textfield.val(this.value).trigger('input');
            });

            // Textfeld -> Picker
            textfield.on('input', function () {
                colorpicker.val(this.value);
                // jetzt updateOverlayPosition() global aufrufen
                if (typeof updateOverlayPosition === 'function') {
                    updateOverlayPosition();
                }
            });

            textfield.after(colorpicker);
            colorpicker.val(textfield.val());
        });

        // Pfeilposition ändern
        $('#id_markerposition').on('change', function() {
            if (typeof updateOverlayPosition === 'function') {
                updateOverlayPosition();
            }
        });

        // Position, Größe und weitere Eingaben
        ['posx', 'posy', 'fixedboxwidth', 'fontsize'].forEach(function(id) {
            $('#id_' + id).on('input', function() {
                if (typeof updateOverlayPosition === 'function') {
                    updateOverlayPosition();
                }
            });
        });

        // Initiales Update beim Laden
        if (typeof updateOverlayPosition === 'function') {
            updateOverlayPosition();
        }
    });
");





$mform->set_data($overlay);
echo "<table><tr><td>";
echo '<div class="overlay-editor-container">';
echo '<div class="overlay-form">';
$mform->display();

$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('toggleadvanced');
    var div = document.getElementById('advancedoptions');
    if (!btn || !div) return;
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (div.style.display === 'none') {
            div.style.display = 'block';
            btn.textContent = 'Erweiterte Optionen verbergen';
        } else {
            div.style.display = 'none';
            btn.textContent = 'Erweiterte Optionen anzeigen';
        }
    });
    
    // Synchronisiere Farbpicker und Textfelder
    function syncColorInput(textId, pickerId) {
        var text = document.getElementById(textId);
        var picker = document.getElementById(pickerId);
        if (!text || !picker) return;
        picker.addEventListener('input', function() {
            text.value = picker.value;
        });
        text.addEventListener('input', function() {
            if(/^#([0-9A-F]{6})$/i.test(text.value)) {
                picker.value = text.value;
            }
        });
    }
    syncColorInput('id_fontcolor', 'id_fontcolorpicker');
    syncColorInput('id_backgroundcolor', 'id_backgroundcolorpicker');
    syncColorInput('id_bordercolor', 'id_bordercolorpicker');


});

JS;

$PAGE->requires->js_init_code($js);



echo '</div>';
echo "</td><td>";
echo '<div class="overlaybox">';

// Video-Vorschau laden
if ($sectionid) {
    $section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);

    // Videoquelle bestimmen (video_source ist maßgeblich; Fallback fürs Erraten bei älteren
    // Abschnitten ohne dieses Feld, analog zu play.php).
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

    $localfileurl = null;
    if ($video_source === 'upload') {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_railvieweradmin', 'sectionvideo', $section->id, 'sortorder', false);
        if ($files) {
            $file = reset($files);
            $localfileurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
        }
    } else if ($video_source === 'sharepoint' && !empty($section->sharepoint_item_id)) {
        // Wie beim Upload ein ganz normaler nativer <video>-Tag (siehe unten, "else"-Zweig) -
        // nur die Quelle ist sharepoint_stream.php statt eine lokale Moodle-Datei.
        $localfileurl = new moodle_url('/local/railvieweradmin/sharepoint_stream.php', ['sectionid' => $section->id]);
    }

    if (in_array($video_source, ['upload', 'sharepoint'], true) && !$localfileurl) {
        echo html_writer::div(get_string('no_video_found_for_section', 'local_railvieweradmin'), 'alert alert-warning');
    } else {
        ?>
        <style>
        #overlaybox {
            position: absolute;
            background: rgba(255, 0, 0, 0.5);
            padding: 5px 5px;
            color: #fff;
            pointer-events: none;
        }
        #overlaybox:focus {
            outline: 3px solid #2196f3;
            outline-offset: 2px;
        }
        #videocontainer {
            position: relative;
            display: inline-block;
        }
        </style>

        <div id="videocontainer">
            <?php if ($video_source === 'vimeo'): ?>
                <h3><?php echo get_string('preview', 'local_railvieweradmin'); ?> (Vimeo)</h3>
                <iframe id="embedplayer"
                        src="https://player.vimeo.com/video/<?php echo (int)$section->vimeo_video_id; ?>?api=1&player_id=embedplayer"
                        width="640"
                        height="360"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen>
                </iframe>
            <?php elseif ($video_source === 'ignite'):
                // "ignite_player_domain" wurde durch "ignite_embed_url_template" (ganzes
                // URL-Muster mit {id}-Platzhalter) ersetzt und per Upgrade-Schritt entfernt.
                $ignite_embed_url_template = get_config('local_railvieweradmin', 'ignite_embed_url_template')
                    ?: 'https://play.ignite.video/player/index.html?id={id}&controls=false';
                ?>
                <h3><?php echo get_string('preview', 'local_railvieweradmin'); ?> (ignite.video)</h3>
                <iframe id="embedplayer"
                        src="<?php echo str_replace('{id}', urlencode($section->ignite_video_id), $ignite_embed_url_template); ?>"
                        width="640"
                        height="360"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen>
                </iframe>
            <?php else: ?>
                <h3><?php echo get_string('preview', 'local_railvieweradmin'); ?></h3>
                <video id="videoplayer" width="640" controls>
                    <source src="<?php echo $localfileurl; ?>" type="video/mp4">
                    <?php echo local_railvieweradmin_get_address_string('browser_no_video_support'); ?>
                </video>
            <?php endif; ?>
            <div id="overlaybox" tabindex="0" role="slider" aria-label="Overlay-Position (mit Pfeiltasten verschiebbar)" aria-valuetext="siehe Positionsfelder">Overlay-Vorschau</div>
        </div>

        <?php if ($video_source === 'vimeo'): ?>
            <script src="https://player.vimeo.com/api/player.js"></script>
        <?php elseif ($video_source === 'ignite'): ?>
            <?php
            // Self-gehostet statt live von cdn.embed.ly geladen - siehe docs/DATENSCHUTZ.md,
            // Abschnitt 5 (#4), und thirdpartylibs/playerjs/player.js für Herkunft/Lizenz.
            $playerjspath = $CFG->dirroot . '/local/railvieweradmin/thirdpartylibs/playerjs/player.js';
            $playerjsurl = new moodle_url('/local/railvieweradmin/thirdpartylibs/playerjs/player.js',
                ['v' => is_file($playerjspath) ? filemtime($playerjspath) : 0]);
            ?>
            <script src="<?php echo $playerjsurl->out(false); ?>"></script>
        <?php endif; ?>

        <script>
            // Overlay-Positionierung/-Styling anhand der Formularfelder - unabhängig davon,
            // welche Videoquelle gerade aktiv ist.
            function updateOverlayPosition() {
                const overlay = document.getElementById('overlaybox');

                const posx = parseFloat(document.getElementById('id_posx').value) || 0;
                const posy = parseFloat(document.getElementById('id_posy').value) || 0;
                overlay.style.left = posx + '%';
                overlay.style.top = posy + '%';
                overlay.style.transform = 'translate(-50%, -50%)';

                const fontsize = parseInt(document.getElementById('id_fontsize')?.value || 12);
                overlay.style.fontSize = fontsize + 'px';

                const fontcolor = document.getElementById('id_fontcolor')?.value || '#000000';
                const backgroundcolor = document.getElementById('id_backgroundcolor')?.value || '#ffffff';
                const bordercolor = document.getElementById('id_bordercolor')?.value || '#000000';

                overlay.style.color = fontcolor;
                overlay.style.backgroundColor = backgroundcolor;
                overlay.style.border = '2px solid ' + bordercolor;

                const fixedboxwidth = parseInt(document.getElementById('id_fixedboxwidth')?.value);
                if (!isNaN(fixedboxwidth) && fixedboxwidth > 0) {
                    overlay.style.width = fixedboxwidth + '%';
                } else {
                    overlay.style.width = 'auto';
                }

                const arrowpos = document.getElementById('id_markerposition')?.value || 'none';
                [
                    'marker-none', 'marker-top-left', 'marker-top', 'marker-top-right',
                    'marker-right', 'marker-bottom-right', 'marker-bottom',
                    'marker-bottom-left', 'marker-left'
                ].forEach(cls => overlay.classList.remove(cls));
                overlay.classList.add('marker-' + arrowpos);
            }

            document.getElementById('id_posx')?.addEventListener('input', updateOverlayPosition);
            document.getElementById('id_posy')?.addEventListener('input', updateOverlayPosition);
            ['id_fontsize', 'id_fontcolor', 'id_backgroundcolor', 'id_bordercolor', 'id_fixedboxwidth']
                .forEach(id => document.getElementById(id)?.addEventListener('input', updateOverlayPosition));
            ['id_fontcolorpicker', 'id_backgroundcolorpicker', 'id_bordercolorpicker']
                .forEach(pickerId => document.getElementById(pickerId)?.addEventListener('input', updateOverlayPosition));
            window.addEventListener('load', updateOverlayPosition);

            const videoContainer = document.getElementById('videocontainer');
            const starttimeInput = document.getElementById('id_starttime');

            videoContainer.addEventListener('dblclick', () => {
                if (!document.fullscreenElement) {
                    videoContainer.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            });

            // Overlay-Drag&Drop - ebenfalls unabhängig von der Videoquelle.
            const overlayBox = document.getElementById('overlaybox');
            const posxInput = document.getElementById('id_posx');
            const posyInput = document.getElementById('id_posy');

            let isDragging = false;
            let startMouseX, startMouseY;
            let startPosXPercent, startPosYPercent;

            overlayBox.style.position = 'absolute';
            overlayBox.style.cursor = 'move';
            overlayBox.style.pointerEvents = 'auto';

            function updateOverlayPositionFromInputs() {
                const x = parseFloat(posxInput.value) || 0;
                const y = parseFloat(posyInput.value) || 0;
                overlayBox.style.left = x + '%';
                overlayBox.style.top = y + '%';
                overlayBox.style.transform = 'translate(-50%, -50%)';
            }

            overlayBox.addEventListener('mousedown', function(e) {
                e.preventDefault();
                isDragging = true;
                startMouseX = e.clientX;
                startMouseY = e.clientY;
                startPosXPercent = parseFloat(posxInput.value) || 0;
                startPosYPercent = parseFloat(posyInput.value) || 0;
                document.body.style.userSelect = 'none';
            });

            // Tastatur-Alternative zum Ziehen mit der Maus (WCAG 2.1.1): Pfeiltasten
            // verschieben das Overlay in 1%-Schritten, mit Shift in 5%-Schritten.
            overlayBox.addEventListener('keydown', function(e) {
                const step = e.shiftKey ? 5 : 1;
                let x = parseFloat(posxInput.value) || 0;
                let y = parseFloat(posyInput.value) || 0;
                let handled = true;

                switch (e.key) {
                    case 'ArrowLeft': x -= step; break;
                    case 'ArrowRight': x += step; break;
                    case 'ArrowUp': y -= step; break;
                    case 'ArrowDown': y += step; break;
                    default: handled = false;
                }

                if (handled) {
                    e.preventDefault();
                    posxInput.value = Math.min(100, Math.max(0, x)).toFixed(1);
                    posyInput.value = Math.min(100, Math.max(0, y)).toFixed(1);
                    updateOverlayPositionFromInputs();
                    updateOverlayPosition();
                }
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                e.preventDefault();

                const deltaX = e.clientX - startMouseX;
                const deltaY = e.clientY - startMouseY;
                const rect = videoContainer.getBoundingClientRect();
                const deltaXPercent = (deltaX / rect.width) * 100;
                const deltaYPercent = (deltaY / rect.height) * 100;

                let newX = Math.min(100, Math.max(0, startPosXPercent + deltaXPercent));
                let newY = Math.min(100, Math.max(0, startPosYPercent + deltaYPercent));

                posxInput.value = newX.toFixed(1);
                posyInput.value = newY.toFixed(1);
                updateOverlayPositionFromInputs();
            });

            document.addEventListener('mouseup', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    isDragging = false;
                    document.body.style.userSelect = '';
                }
            });

            updateOverlayPositionFromInputs();

            function updateOverlayText() {
                const nameInput = document.getElementById('id_name');
                const overlay = document.getElementById('overlaybox');
                if (nameInput && overlay) {
                    const text = nameInput.value.trim();
                    overlay.textContent = text !== '' ? text : 'noch kein Text eingegeben';
                }
            }
            document.getElementById('id_name')?.addEventListener('input', updateOverlayText);
            window.addEventListener('load', updateOverlayText);

            // Player-spezifische Zeit-Integration: Vimeo und ignite.video (player.js) haben
            // eine sehr ähnliche, aber nicht identische API (siehe setupVimeoPlayer/
            // setupIgnitePlayer in mod_railviewer/play.php für Details zu den Unterschieden).
            <?php if ($video_source === 'vimeo'): ?>
            const iframe = document.querySelector('#embedplayer');
            const player = new Vimeo.Player(iframe);

            player.on('timeupdate', function(data) {
                const currentTime = data.seconds;
                if (starttimeInput && document.activeElement !== starttimeInput) {
                    starttimeInput.value = Math.floor(currentTime);
                }
                manageOverlays(currentTime);
            });

            starttimeInput?.addEventListener('input', () => {
                const time = parseFloat(starttimeInput.value);
                if (!isNaN(time) && time >= 0) {
                    player.setCurrentTime(time);
                }
            });

            window.jumpToTime = function(time) { player.setCurrentTime(time); };

            <?php elseif ($video_source === 'ignite'): ?>
            const iframe = document.querySelector('#embedplayer');
            const player = new playerjs.Player(iframe);

            player.on('ready', () => {
                player.on('timeupdate', function(data) {
                    const currentTime = data.seconds;
                    if (starttimeInput && document.activeElement !== starttimeInput) {
                        starttimeInput.value = Math.floor(currentTime);
                    }
                    manageOverlays(currentTime);
                });
            });

            starttimeInput?.addEventListener('input', () => {
                const time = parseFloat(starttimeInput.value);
                if (!isNaN(time) && time >= 0) {
                    player.setCurrentTime(time);
                }
            });

            window.jumpToTime = function(time) { player.setCurrentTime(time); };

            <?php else: ?>
            const video = document.getElementById('videoplayer');

            video.addEventListener('timeupdate', function() {
                const currentTime = video.currentTime;
                if (starttimeInput && document.activeElement !== starttimeInput) {
                    starttimeInput.value = Math.floor(currentTime);
                }
                manageOverlays(currentTime);
            });

            starttimeInput?.addEventListener('input', () => {
                const time = parseFloat(starttimeInput.value);
                if (!isNaN(time) && time >= 0 && time <= video.duration) {
                    video.currentTime = time;
                }
            });

            window.jumpToTime = function(time) { video.currentTime = time; };
            <?php endif; ?>
        </script>
        <?php
    }
}

echo '</div>';
echo '</div>';
echo "</td></tr></table>";

// Overlay-Tabelle anzeigen
if ($sectionid) {
    echo '<div class="overlay-table-container mt-4">';
    echo '<h3>Vorhandene Overlays für diesen Abschnitt</h3>';
    echo '<div id="overlay-table-content">';
    // Initial leer - wird per JavaScript geladen
    echo '</div>';
    echo '</div>';
}

// JavaScript für Ajax-Funktionalität hinzufügen
?>
<script>
let allOverlays = [];
let currentlyVisibleOverlays = [];

// Funktion zum Laden aller Overlays
function loadAllOverlays() {
    const sectionId = <?php echo $sectionid; ?>;
    if (!sectionId) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_overlays.php?sectionid=' + sectionId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    allOverlays = response.overlays.sort((a, b) => parseFloat(a.starttime) - parseFloat(b.starttime));
                }
            } catch(e) {
                console.error('Fehler beim Laden der Overlays:', e);
            }
        }
    };
    xhr.send();
}

// Zentrale Overlay-Management Funktion - läuft IMMER
function manageOverlays(currentTime) {
    // Debug-Ausgabe alle 5 Sekunden
    if (Math.floor(currentTime) % 5 === 0 && currentTime % 1 < 0.1) {
        //console.log(`Zeit: ${currentTime.toFixed(1)}s, Aktive Overlays: ${currentlyVisibleOverlays.length}`);
    }
    
    // 1. Prüfen welche Overlays angezeigt werden sollen
    const overlaysToShow = allOverlays.filter(overlay => {
        const startTime = parseFloat(overlay.starttime);
        const endTime = parseFloat(overlay.endtime);
        const shouldShow = currentTime >= startTime && currentTime <= endTime;
        
        // Debug für einzelne Overlays
        if (shouldShow && !currentlyVisibleOverlays.find(v => v.id === overlay.id)) {
            //console.log(`Overlay "${overlay.name}" wird angezeigt (${startTime}s - ${endTime}s)`);
        }
        
        return shouldShow;
    });
    
    // 2. Overlays aktualisieren
    updateVisibleOverlays(overlaysToShow);
}

// Funktion zum Aktualisieren der sichtbaren Overlays
function updateVisibleOverlays(overlaysToShow) {
    const videoContainer = getOverlayContainer();
    if (!videoContainer) {
        console.warn('Kein Video-Container gefunden');
        return;
    }
    
    // Prüfen ob sich etwas geändert hat
    const currentIds = currentlyVisibleOverlays.map(o => o.id).sort();
    const newIds = overlaysToShow.map(o => o.id).sort();
    
    if (JSON.stringify(currentIds) === JSON.stringify(newIds)) {
        return; // Keine Änderung
    }
    
    // Alle bestehenden dynamischen Overlays entfernen
    const existingOverlays = videoContainer.querySelectorAll('.dynamic-overlay');
    existingOverlays.forEach(overlay => overlay.remove());
    
    // Neue Overlays hinzufügen
    overlaysToShow.forEach(overlay => {
        createAndShowOverlay(overlay, videoContainer);
    });
    
    currentlyVisibleOverlays = overlaysToShow;
    
    if (overlaysToShow.length > 0) {
        //console.log(`${overlaysToShow.length} Overlay(s) werden angezeigt:`, overlaysToShow.map(o => o.name));
    }
}

// Funktion zum Erstellen und Anzeigen eines Overlays
function createAndShowOverlay(overlay) {
    const videoContainer = document.getElementById('videocontainer');
    if (!videoContainer) return;
    
    const overlayElement = document.createElement('div');
    overlayElement.className = 'dynamic-overlay marker-' + (overlay.markerposition || 'none');
    overlayElement.style.cssText = `
        position: absolute;
        left: ${overlay.posx}%;
        top: ${overlay.posy}%;
        transform: translate(-50%, -50%);
        padding: 5px 10px;
        font-size: ${overlay.fontsize || 12}px;
        color: ${overlay.fontcolor || '#000000'};
        background-color: ${overlay.backgroundcolor || '#ffffff'};
        border: 2px solid ${overlay.bordercolor || '#000000'};
        white-space: pre-wrap;
        word-wrap: break-word;
        z-index: 100;
        pointer-events: none;
        --bordercolor: ${overlay.bordercolor || '#000000'};
        ${overlay.fixedboxwidth > 0 ? `width: ${overlay.fixedboxwidth}%;` : 'width: auto;'}
        max-width: 80%;
    `;
    
    overlayElement.textContent = overlay.name || 'Overlay';

    videoContainer.appendChild(overlayElement);
}

// CSS für Pulse-Animation hinzufügen
function addOverlayStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .dynamic-overlay {
            transition: all 0.3s ease-in-out;
        }
        
        /* Pfeil-Styles für dynamische Overlays */
        .dynamic-overlay::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border-style: solid;
        }
        
        .dynamic-overlay.marker-none::after { display: none; }
        
        .dynamic-overlay.marker-top-left::after {
            bottom: 100%; left: 2px;
            border-width: 0 10px 10px 10px;
            border-color: transparent transparent var(--bordercolor) transparent;
        }
        
        .dynamic-overlay.marker-top::after {
            bottom: 100%; left: 50%; transform: translateX(-50%);
            border-width: 0 10px 10px 10px;
            border-color: transparent transparent var(--bordercolor) transparent;
        }
        
        .dynamic-overlay.marker-top-right::after {
            bottom: 100%; right: 2px;
            border-width: 0 10px 10px 10px;
            border-color: transparent transparent var(--bordercolor) transparent;
        }
        
        .dynamic-overlay.marker-right::after {
            top: 50%; left: 100%; transform: translateY(-50%);
            border-width: 10px 0 10px 10px;
            border-color: transparent transparent transparent var(--bordercolor);
        }
        
        .dynamic-overlay.marker-bottom-right::after {
            top: 100%; right: 2px;
            border-width: 10px 10px 0 10px;
            border-color: var(--bordercolor) transparent transparent transparent;
        }
        
        .dynamic-overlay.marker-bottom::after {
            top: 100%; left: 50%; transform: translateX(-50%);
            border-width: 10px 10px 0 10px;
            border-color: var(--bordercolor) transparent transparent transparent;
        }
        
        .dynamic-overlay.marker-bottom-left::after {
            top: 100%; left: 2px;
            border-width: 10px 10px 0 10px;
            border-color: var(--bordercolor) transparent transparent transparent;
        }
        
        .dynamic-overlay.marker-left::after {
            top: 50%; right: 100%; transform: translateY(-50%);
            border-width: 10px 10px 10px 0;
            border-color: transparent var(--bordercolor) transparent transparent;
        }
    `;
    document.head.appendChild(style);
}
// Overlay-Tabelle laden
function loadOverlayTable() {
    const sectionId = <?php echo $sectionid; ?>;
    if (!sectionId) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_overlays.php?sectionid=' + sectionId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const rawResponse = xhr.responseText.trim();
                    const response = JSON.parse(rawResponse);



                    if (response.success) {
                        renderOverlayTable(response.overlays);


                    } else {
                        document.getElementById('overlay-table-content').innerHTML = 
                            '<div class="alert alert-danger">' + (response.message || 'Fehler beim Laden der Overlays') + '</div>';
                    }
                } catch(e) {
                    document.getElementById('overlay-table-content').innerHTML = 
                        '<div class="alert alert-danger">Fehler beim Parsen der Antwort</div>';
                }
            } else {
                document.getElementById('overlay-table-content').innerHTML = 
                    '<div class="alert alert-danger">Fehler beim Laden. Status: ' + xhr.status + '</div>';
            }
        }
    };
    xhr.send();
    // Am Ende der loadOverlayTable Funktion
    loadAllOverlays();
}

// Overlay-Tabelle rendern
function renderOverlayTable(overlays) {
    let html = '';
    
    if (overlays.length === 0) {
        html = '<div class="alert alert-info">Noch keine Overlays für diesen Abschnitt vorhanden.</div>';
    } else {
        html += '<table class="table table-striped table-hover">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>Name</th>';
        html += '<th>Startzeit (s)</th>';
        html += '<th>Endzeit (s)</th>';
        html += '<th>Position X (%)</th>';
        html += '<th>Position Y (%)</th>';
        html += '<th>Aktionen</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
       

        overlays.forEach(function(overlay) {
            const fontcolor = overlay.fontcolor || '#000000';
            const bgcolor = overlay.backgroundcolor || '#ffffff';
            const bordercolor = overlay.bordercolor || '#000000';
            const fontsize = overlay.fontsize || 12;
            const width = Number.isFinite(Number(overlay.fixedboxwidth)) ? overlay.fixedboxwidth + '%' : 'auto';
            const markerclass = 'marker-' + (overlay.markerposition || 'none');
            const safeName = (overlay.name || 'Ohne Name')
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")
                .replace(/\n/g, "<br>");

            const formattedName = `<div class="overlay-preview ${markerclass}" style="
    display: inline-block;
    position: relative;
    padding: 2px 2px;
    font-size: ${fontsize}px;
    color: ${fontcolor};
    background-color: ${bgcolor};
    border: 2px solid ${bordercolor};
    max-width: 100%;
    /*width: ${width};*/
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 5px 0;
">${safeName}</div>`;

            html += `<tr class="overlay-row"
             data-id="${overlay.id}"
             data-name="${overlay.name}"
             data-starttime="${overlay.starttime}"
             data-endtime="${overlay.endtime}"
             data-posx="${overlay.posx}"
             data-posy="${overlay.posy}"
             style="cursor: pointer;" 
             title="Klicken um zu Endzeit zu springen">`;

            html += `<td>${formattedName}</td>`;
            html += `<td>${overlay.starttime}</td>`;
            html += `<td>${overlay.endtime}</td>`;
            html += `<td>${overlay.posx}</td>`;
            html += `<td>${overlay.posy}</td>`;
            html += `<td class="action-buttons">
                        <button type="button" class="btn btn-sm btn-primary me-1" onclick="editOverlay(${overlay.id})">Bearbeiten</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteOverlay(${overlay.id})">Löschen</button>
                     </td>`;
            html += `</tr>`;
        });

        
        html += '</tbody>';
        html += '</table>';
    }
    
    document.getElementById('overlay-table-content').innerHTML = html;
    
    // Event-Listener für Zeilen-Klick hinzufügen
    const overlayRows = document.querySelectorAll('.overlay-row');
    overlayRows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Nicht reagieren wenn auf Button geklickt wurde
            if (e.target.tagName === 'BUTTON' || e.target.closest('.action-buttons')) {
                return;
            }
            
            const endtime = parseFloat(this.getAttribute('data-endtime'));
            if (!isNaN(endtime) && typeof window.jumpToTime === 'function') {
                window.jumpToTime(endtime);
                // Visuelles Feedback
                this.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 1000);
            }
        });
    });
}

// Overlay bearbeiten
function editOverlay(overlayId) {
    window.location.href = 'edit_overlay.php?id=' + overlayId + '&sectionid=<?php echo $sectionid; ?>';
}

// Overlay löschen
function deleteOverlay(overlayId) {
    if (!confirm('Sind Sie sicher, dass Sie dieses Overlay löschen möchten?')) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/delete_overlay.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loadOverlayTable(); // Tabelle neu laden
                        showMessage('Overlay wurde erfolgreich gelöscht', 'success');
                    } else {
                        showMessage('Fehler beim Löschen: ' + (response.message || 'Unbekannter Fehler'), 'error');
                    }
                } catch(e) {
                    showMessage('Fehler beim Parsen der Antwort', 'error');
                }
            } else {
                showMessage('Fehler beim Löschen. Status: ' + xhr.status, 'error');
            }
        }
    };
    xhr.send('id=' + overlayId + '&sesskey=' + encodeURIComponent(M.cfg.sesskey));
}

// Ajax-Formular-Übermittlung (Ersatz für den bestehenden setupAjaxForm)
// Vereinfachte Ajax-Formular-Übermittlung
function setupAjaxForm() {
    const form = document.getElementById('overlay-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Verhindert normales Submit
        
        const formData = new FormData(form);
        formData.set('ajax', '1'); // AJAX-Flag setzen
        
        // DEBUG: FormData loggen
        //console.log('=== SENDING FORM DATA ===');
        for (let [key, value] of formData.entries()) {
            //console.log(key + ': ' + value);
        }
        //console.log("URL: "+window.location.href);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true); // An die gleiche URL senden
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                //console.log('Server Response Status:', xhr.status);
                //console.log('Server Response Text:', xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showMessage(response.message, 'success');
                            loadOverlayTable(); // Tabelle neu laden
                            
                            // Wenn neues Overlay erstellt wurde, Formular zurücksetzen
                            if (response.message.includes('erstellt')) {
                                // Werte zurücksetzen aber Formular-Struktur beibehalten
                                document.getElementById('id_name').value = '';
                                document.getElementById('id_posx').value = '0';
                                document.getElementById('id_posy').value = '0';

                                if (typeof updateOverlayPosition === 'function') {
                                    updateOverlayPosition();
                                }
                            }
                        } else {
                            showMessage('Fehler: ' + (response.message || 'Unbekannter Fehler'), 'error');
                        }
                    } catch(e) {
                        console.error('Parse Error:', e, xhr.responseText);
                        showMessage('Fehler beim Parsen der Antwort: ' + xhr.responseText, 'error');
                    }
                } else {
                    showMessage('Fehler beim Speichern. Status: ' + xhr.status, 'error');
                }
            }
        };
        
        xhr.send(formData);
    });
}

// Nachricht anzeigen
function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const messageHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                       '</div>';
    
    // Nachricht oberhalb des Formulars einfügent
    const formContainer = document.querySelector('.overlay-form');
    if (formContainer) {
        const existingAlert = formContainer.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        formContainer.insertAdjacentHTML('afterbegin', messageHtml);
    }
}
// Container für Overlays ermitteln (normal oder fullscreen)
function getOverlayContainer(isFullscreen) {
   /*if (isFullscreen===true) {
        return document.fullscreenElement || 
               document.webkitFullscreenElement || 
               document.mozFullScreenElement || 
               document.msFullscreenElement;
    } else {*/
        return document.getElementById('videocontainer');
    //}
}

// Seite initialisieren
window.addEventListener('load', function() {
    addOverlayStyles(); // CSS hinzufügen
    loadOverlayTable(); // Lädt auch alle Overlays
    setupAjaxForm();
});



</script>

<?php

echo $OUTPUT->footer();
?>
<style>

.overlay-editor-container {
    display: flex;
    flex-wrap: wrap; /* wichtig: kein Umbruch */
    gap: 20px;
    align-items: flex-start;
}

.overlay-form,
.overlaybox {
    flex: 0 0 50%;   /* feste Breite */
    min-width: 300px;
    box-sizing: border-box;
}
#videocontainer {
    position: relative;
    display: inline-block;
}
#overlaybox {
  position: absolute;
  background: rgba(255, 0, 0, 0.5);
  padding: 5px 10px;
  color: #fff;
  pointer-events: auto;
  cursor: move;
  z-index: 10;
    white-space: pre-wrap;
 /* border: 2px solid #fff; falls gewünscht */
/*  border-radius: 10px;*/
  transform: translate(-50%, -50%);
/*  max-width: 80%;*/
  --bordercolor: #fff; /* Standardwert für Pfeilrandfarbe */
}

#overlaybox::after {
  content: '';
  position: absolute;
  width: 0;
  height: 0;
  border-style: solid;
}

/* No marker at all*/
.marker-none::after {
  display: none;
}
/* TOP-LEFT */
.marker-top-left::after {
  bottom: 100%;
  left: 2px;
  border-width: 0 10px 10px 10px;
  border-color: transparent transparent var(--bordercolor) transparent;
}

/* TOP */
.marker-top::after {
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-width: 0 10px 10px 10px;
  border-color: transparent transparent var(--bordercolor) transparent;
}

/* TOP-RIGHT */
.marker-top-right::after {
  bottom: 100%;
  right: 2px;
  border-width: 0 10px 10px 10px;
  border-color: transparent transparent var(--bordercolor) transparent;
}

/* RIGHT */
.marker-right::after {
  top: 50%;
  left: 100%;
  transform: translateY(-50%);
  border-width: 10px 0 10px 10px;
  border-color: transparent transparent transparent var(--bordercolor);
}

/* BOTTOM-RIGHT */
.marker-bottom-right::after {
  top: 100%;
  right: 2px;
  border-width: 10px 10px 0 10px;
  border-color: var(--bordercolor) transparent transparent transparent;
}

/* BOTTOM */
.marker-bottom::after {
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-width: 10px 10px 0 10px;
  border-color: var(--bordercolor) transparent transparent transparent;
}

/* BOTTOM-LEFT */
.marker-bottom-left::after {
  top: 100%;
  left: 2px;
  border-width: 10px 10px 0 10px;
  border-color: var(--bordercolor) transparent transparent transparent;
}

/* LEFT */
.marker-left::after {
  top: 50%;
  right: 100%;
  transform: translateY(-50%);
  border-width: 10px 10px 10px 0;
  border-color: transparent var(--bordercolor) transparent transparent;
}




.overlay-table-container {
    width: 100%;
    margin-top: 20px;
}
.overlay-table-container .table {
    margin-top: 15px;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.me-1 {
    margin-right: 0.25rem;
}
.overlay-row {
    transition: background-color 0.3s ease;
}
.overlay-row:hover {
    background-color: #f8f9fa !important;
}
.action-buttons {
    white-space: nowrap;
}
</style>

