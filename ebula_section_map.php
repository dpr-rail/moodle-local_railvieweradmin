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
require_capability('local/railvieweradmin:editsections', $context);

$sectionid = optional_param('id', 1, PARAM_INT); 

// DB-Instanz
global $DB;

// Daten aus DB laden
$points = $DB->get_records('railviewer_ebula_section_map', ['sectionid' => $sectionid], 'videotime ASC');
$section = $DB->get_record('railviewer_sections',['id' => $sectionid], '*', MUST_EXIST);
$timetable = $DB->get_record('railviewer_ebula_section',['sectionid' => $sectionid], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/local/railvieweradmin/ebula_section_map.php', ['id' => $sectionid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('map_timetable_to_video', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('map_timetable_to_video', 'local_railvieweradmin'));

echo $OUTPUT->header();

// Videoquelle bestimmen (video_source ist maßgeblich; Fallback fürs Erraten bei älteren
// Abschnitten ohne dieses Feld, analog zu play.php/edit_overlay.php).
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
// "ignite_player_domain" wurde durch "ignite_embed_url_template" (ganzes URL-Muster mit
// {id}-Platzhalter) ersetzt und per Upgrade-Schritt entfernt.
$ignite_embed_url_template = get_config('local_railvieweradmin', 'ignite_embed_url_template')
    ?: 'https://play.ignite.video/player/index.html?id={id}&controls=false';
?>

<style>
  #container {
    display: flex;
    gap: 20px;
  }
  #video-wrapper {
    flex: 1;
  }
  #table-wrapper {
    flex: 1;
    max-height: 500px;
    overflow-y: auto;
    position: relative;
    border: 1px solid #ccc;
  }
  table.simplified {
    border-collapse: collapse;
    width: 100%;
  }
  table.simplified th, table.simplified td {
    border: 1px solid #999;
    padding: 4px;
  }
  #yellow-line {
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: yellow;
    pointer-events: none;
    z-index: 10;
  }
  #points-list {
    margin-top: 20px;
  }
  table {border-collapse: collapse; width: 100%;}
  td, th {border-left: 1px solid #ccc; border-right: 1px solid black; padding: 4px; text-align: left;}
  .simplified {border: 1px solid black; border-collapse: collapse; width: 100%; margin-bottom: 0.5em;}
  .simplified td, .simplified th {border-left: 1px solid black; border-right: 1px solid black; padding: 2px;}
  .simplified td:first-child, .simplified th:first-child {border-left: none;}
  .simplified td:last-child, .simplified th:last-child {border-right: none;}
  .inverse-speed { background-color: black; color: white; }
</style>

<div id="container">
  <div id="video-wrapper">
    <?php if ($video_source === 'vimeo'): ?>
        <!-- Vimeo Player -->
        <iframe id="embedplayer"
                src="https://player.vimeo.com/video/<?php echo (int)$section->vimeo_video_id; ?>?api=1&player_id=embedplayer"
                width="100%"
                height="360"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen>
        </iframe>
    <?php elseif ($video_source === 'ignite'): ?>
        <!-- ignite.video Player -->
        <iframe id="embedplayer"
                src="<?php echo str_replace('{id}', urlencode($section->ignite_video_id), $ignite_embed_url_template); ?>"
                width="100%"
                height="360"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen>
        </iframe>
    <?php else: ?>
        <!-- Lokales Video oder SharePoint-Stream -->
        <?php
        $videoUrl = '';
        if ($video_source === 'sharepoint' && !empty($section->sharepoint_item_id)) {
            $videoUrl = new moodle_url('/local/railvieweradmin/sharepoint_stream.php', ['sectionid' => $section->id]);
        } else {
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'local_railvieweradmin', 'sectionvideo', $section->id, 'sortorder', false);
            if ($files) {
                $file = reset($files);
                $videoUrl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                );
            }
        }
        if ($videoUrl) {
        ?>
            <video id="video" width="100%" controls>
                <source src="<?php echo $videoUrl ?>" type="video/mp4">
                <?php echo local_railvieweradmin_get_address_string('browser_no_video_support'); ?>
            </video>
        <?php } else { ?>
            <div class="alert alert-warning"><?php echo get_string('no_video_found_for_section', 'local_railvieweradmin'); ?></div>
        <?php } ?>
    <?php endif; ?>
    <p><?php echo get_string('current_time', 'local_railvieweradmin'); ?>: <span id="current-time">0.00</span> s</p>
    <button id="btn-add-point"><?php echo get_string('save_position', 'local_railvieweradmin'); ?></button>
    <label><input type="checkbox" id="chk-static"> <?php echo get_string('still_image_no_line_movement', 'local_railvieweradmin'); ?></label>
  </div>

  <div id="table-wrapper">
    <div id="yellow-line"></div>
    <table class="simplified" id="data-table">
      <thead>
        <tr>
          <th><?php echo get_string('km', 'local_railvieweradmin'); ?></th>
          <th><?php echo get_string('speed', 'local_railvieweradmin'); ?></th>
          <th><?php echo get_string('station', 'local_railvieweradmin'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?= format_text($timetable->html, FORMAT_HTML, ['noclean' => false, 'context' => $context]); ?>
      </tbody>
    </table>
  </div>
</div>

<div id="section-map-table">
     <?php
        $sectionid = $section->id;
        include(__DIR__ . '/ebula_section_map_table.php');
        ?>
</div>

<?php if ($video_source === 'vimeo'): ?>
<script src="https://player.vimeo.com/api/player.js"></script>
<?php elseif ($video_source === 'ignite'): ?>
<?php
// Self-gehostet statt live von cdn.embed.ly geladen - siehe docs/DATENSCHUTZ.md, Abschnitt 5
// (#4), und thirdpartylibs/playerjs/player.js für Herkunft/Lizenz.
$playerjspath = $CFG->dirroot . '/local/railvieweradmin/thirdpartylibs/playerjs/player.js';
$playerjsurl = new moodle_url('/local/railvieweradmin/thirdpartylibs/playerjs/player.js',
    ['v' => is_file($playerjspath) ? filemtime($playerjspath) : 0]);
?>
<script src="<?php echo $playerjsurl->out(false); ?>"></script>
<?php endif; ?>
<script>
const sectionId = <?= (int)$section->id ?>;
const ebulaMapSesskey = <?php echo json_encode(sesskey()); ?>;
const videoSource = <?php echo json_encode($video_source); ?>;

const yellowLine = document.getElementById('yellow-line');
const tableWrapper = document.getElementById('table-wrapper');
const btnAddPoint = document.getElementById('btn-add-point');
const chkStatic = document.getElementById('chk-static');

// Player-Adapter: kapselt die Unterschiede zwischen Vimeo, ignite.video (player.js) und
// lokalem <video>-Element hinter einer einheitlichen Schnittstelle (getCurrentTime als
// Promise, setCurrentTime, onTimeUpdate), damit der restliche Code quellenunabhängig ist.
let getCurrentTime = () => Promise.resolve(0);
let setCurrentTime = () => {};
let onTimeUpdate = () => {};

if (videoSource === 'vimeo') {
    const iframe = document.querySelector('#embedplayer');
    const player = new Vimeo.Player(iframe);
    getCurrentTime = () => player.getCurrentTime();
    setCurrentTime = (t) => player.setCurrentTime(t);
    onTimeUpdate = (cb) => player.on('timeupdate', data => cb(data.seconds));
} else if (videoSource === 'ignite') {
    const iframe = document.querySelector('#embedplayer');
    const player = new playerjs.Player(iframe);
    const playerReady = new Promise(resolve => player.on('ready', resolve));
    getCurrentTime = () => new Promise(resolve => player.getCurrentTime(resolve));
    setCurrentTime = (t) => player.setCurrentTime(t);
    onTimeUpdate = (cb) => playerReady.then(() => player.on('timeupdate', data => cb(data.seconds)));
} else {
    const video = document.getElementById('video');
    getCurrentTime = () => Promise.resolve(video ? video.currentTime : 0);
    setCurrentTime = (t) => { if (video) video.currentTime = t; };
    onTimeUpdate = (cb) => { if (video) video.addEventListener('timeupdate', () => cb(video.currentTime)); };
}

// Mapping von Zeit und Positionen aus PHP (DB)
const lineMappings = <?php
    $jspoints = [];
    foreach ($points as $p) {
        $jspoints[] = [
            'time' => (float)$p->videotime,
            'top' => (int)$p->line_top,
            'static' => (bool)$p->is_static,
        ];
    }
    echo json_encode($jspoints);
?>;

// Sortieren nach Zeit (für Interpolation)
lineMappings.sort((a, b) => a.time - b.time);

// Hilfsfunktion: finde Punkte links und rechts von t
function findSurroundingPoints(t, arr) {
    let prev = null, next = null;
    for (let i = 0; i < arr.length; i++) {
        if (arr[i].time <= t) prev = arr[i];
        if (arr[i].time > t) {
            next = arr[i];
            break;
        }
    }
    return [prev, next];
}

document.addEventListener('DOMContentLoaded', function () {
    bindDeleteButtons();
    bindRowClickEvents();
});

function bindDeleteButtons() {
    document.querySelectorAll('.delete-point').forEach(btn => {
        btn.addEventListener('click', e => {
            const id = e.target.getAttribute('data-id');
            if (confirm('<?php echo get_string('confirm_delete', 'local_railvieweradmin'); ?>')) {
                fetch('ebula_section_map_delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id, sesskey: ebulaMapSesskey})
                })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        reloadTable();
                    } else {
                        alert('<?php echo get_string('delete_error', 'local_railvieweradmin'); ?>: ' + json.message);
                    }
                });
            }
        });
    });
}

function bindRowClickEvents() {
    document.querySelectorAll('.section-map-row').forEach(row => {
        row.addEventListener('click', () => {
            const time = parseFloat(row.dataset.videotime);
            if (!isNaN(time)) {
                setCurrentTime(time);
            }
        });
    });
}

function reloadTable() {
    fetch('<?php echo (new moodle_url("/local/railvieweradmin/ebula_section_map_table.php"))->out(); ?>?sectionid=' + sectionId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('section-map-table').innerHTML = html;
            bindDeleteButtons();
            bindRowClickEvents();
            updateLineMappings();
        });
}

// Aktualisiert lineMappings aus der Datenbank (quellenunabhängig).
function updateLineMappings() {
    fetch('<?php echo new moodle_url('/local/railvieweradmin/ebula_section_map_get.php'); ?>?sectionid=' + sectionId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                lineMappings.length = 0;
                data.points.forEach(point => {
                    lineMappings.push({
                        time: parseFloat(point.videotime),
                        top: parseInt(point.line_top),
                        static: Boolean(point.is_static),
                    });
                });
                lineMappings.sort((a, b) => a.time - b.time);
                getCurrentTime().then(updateYellowLine);
            }
        })
        .catch(error => {
            console.error('<?php echo get_string('error_loading_mappings', 'local_railvieweradmin'); ?>:', error);
        });
}

// Aktualisiere gelbe Linie bei Videofortschritt.
onTimeUpdate(function(t) {
    document.getElementById('current-time').textContent = t.toFixed(2);
    updateYellowLine(t);
});

function updateYellowLine(t) {
    const [prev, next] = findSurroundingPoints(t, lineMappings);

    if (!prev) {
        yellowLine.style.top = '0px';
        return;
    }
    if (!next || prev.static) {
        yellowLine.style.top = prev.top + 'px';
        return;
    }

    const factor = (t - prev.time) / (next.time - prev.time);
    const interpolatedTop = prev.top + factor * (next.top - prev.top);
    yellowLine.style.top = interpolatedTop + 'px';
}

// Ermittle Klickposition relativ zu Tabelle für neue Linie
let currentNewTop = 0;
tableWrapper.addEventListener('click', (ev) => {
    const rect = tableWrapper.getBoundingClientRect();
    const clickY = ev.clientY - rect.top + tableWrapper.scrollTop;
    yellowLine.style.top = clickY + 'px';
    currentNewTop = clickY;
});

btnAddPoint.addEventListener('click', () => {
    getCurrentTime().then(function(currentTime) {
        const videotime = currentTime;
        const line_top = currentNewTop;
        const is_static = chkStatic.checked ? 1 : 0;

        if (!line_top) {
            alert('<?php echo get_string('click_table_first_to_choose_position', 'local_railvieweradmin'); ?>');
            return;
        }

        fetch('<?php echo new moodle_url('/local/railvieweradmin/ebula_section_map_save.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                sectionid: sectionId,
                videotime: videotime,
                line_top: line_top,
                is_static: is_static,
                sesskey: ebulaMapSesskey
            })
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                alert('<?php echo get_string('point_saved', 'local_railvieweradmin'); ?>');
                setCurrentTime(currentTime);
                reloadTable();
            } else {
                alert('<?php echo get_string('save_error', 'local_railvieweradmin'); ?>: ' + data.message);
            }
        });
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
