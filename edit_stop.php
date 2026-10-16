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

$id = optional_param('id', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/edit_stop.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('edit_stop', 'local_railvieweradmin') : get_string('create_new_stop', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('edit_stop', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_sections', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index_sections.php'));
$PAGE->navbar->add($id ? get_string('edit_stop', 'local_railvieweradmin') : get_string('create_new_stop', 'local_railvieweradmin'));

echo $OUTPUT->header();

global $DB;

// stop-Daten vorbereiten
if ($id) {
    $stop = $DB->get_record('railviewer_stops', ['id' => $id], '*', MUST_EXIST);
    $sectionid = $stop->sectionid;
} else {
    $stop = new stdClass();
    $stop->id = 0;
    $stop->sectionid = $sectionid;
    $stop->name = '';
    $stop->stoptime = 0;
}

$mform = new \local_railvieweradmin\forms\stop_form(null, ['data' => $stop]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('index_section_stops.php?sectionid='.$sectionid));
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        $DB->update_record('railviewer_stops', $data);
    } else {
        unset($data->id);
        $DB->insert_record('railviewer_stops', $data);
    }
    redirect(new moodle_url('index_section_stops.php?sectionid='.$sectionid),
        get_string('stop_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$mform->set_data($stop);
echo '<div class="stop-editor-container">';
echo '<div class="stop-form">';
$mform->display();
echo '</div>';
echo '<div class="stop-preview">';

// Video-Vorschau laden
if ($sectionid) {
    $section = $DB->get_record('railviewer_sections', ['id' => $sectionid], '*', MUST_EXIST);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_railvieweradmin', 'sectionvideo', $section->id, 'sortorder', false);
    if ($files) {
        $file = reset($files);
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        ?>
        <h3><?php echo get_string('preview', 'local_railvieweradmin'); ?></h3>
        <div id="videocontainer">
            <video id="videoplayer" width="640" controls>
                <source src="<?php echo $url; ?>" type="video/mp4">
                <?php echo local_railvieweradmin_get_address_string('browser_no_video_support'); ?>
            </video>
        </div>

        <script>
            const video = document.getElementById('videoplayer');
            const stoptimeInput = document.getElementById('id_stoptime');
            const stopnameInput = document.getElementById('id_name');
            let stopTriggered = false;
            const defaultStopName = <?php echo json_encode(get_string('stop_reached_default_name', 'local_railvieweradmin')); ?>;
            const stoppedAtTemplate = <?php echo json_encode(get_string('video_stopped_at_template', 'local_railvieweradmin')); ?>;

            // Video schreibt Zeit ins Startzeit-Input (solange nicht aktiv bearbeitet)
            video.addEventListener('timeupdate', () => {
                const currentTime = video.currentTime;

                if (stoptimeInput && document.activeElement !== stoptimeInput) {
                    stoptimeInput.value = Math.floor(currentTime);
                }

                const stoptime = parseFloat(stoptimeInput.value);
                if (!stopTriggered && !isNaN(stoptime) && currentTime >= stoptime) {
                    video.pause();
                    stopTriggered = true;

                    const stopName = stopnameInput?.value?.trim() || defaultStopName;
                    alert(stoppedAtTemplate.replace('__NAME__', stopName).replace('__TIME__', Math.floor(stoptime)));
                }
            });

            // Wenn Nutzer Zeit ins Startfeld eingibt, springt Video
            stoptimeInput?.addEventListener('input', () => {
                const time = parseFloat(stoptimeInput.value);
                if (!isNaN(time) && time >= 0 && time <= video.duration) {
                    video.currentTime = time;
                }
            });

            // Wenn Video neu gestartet wird, darf es wieder stoppen
            video.addEventListener('play', () => {
                stopTriggered = false;
            });

            // Doppelklick -> Fullscreen
            const videoContainer = document.getElementById('videocontainer');
            video.addEventListener('dblclick', () => {
                if (!document.fullscreenElement) {
                    videoContainer.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            });
        </script>

        <?php
    }
}

echo '</div></div>'; // close .stop-preview & .stop-editor-container
echo $OUTPUT->footer();
?>

<style>
.stop-editor-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-start;
}
.stop-form {
    flex: 1 1 45%;
    min-width: 300px;
}
.stop-preview {
    flex: 1 1 45%;
    min-width: 300px;
}
#videocontainer {
    position: relative;
    display: inline-block;
}
</style>
