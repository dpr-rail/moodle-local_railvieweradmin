<?php

require_once(__DIR__.'/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

// Hole alle gespeicherten Punkte zu diesem Abschnitt
global $DB;

if (!isset($sectionid)) {
    $sectionid = optional_param('sectionid', 0, PARAM_INT);
}

if (!$sectionid) {
    throw new moodle_exception('sectionid_missing', 'local_railvieweradmin');
}

$records = $DB->get_records('railviewer_ebula_section_map', ['sectionid' => $sectionid], 'videotime DESC');

?>
<table class="generaltable">
    <thead>
        <tr>
            <th><?php echo get_string('time_seconds', 'local_railvieweradmin'); ?></th>
            <th><?php echo get_string('position_px', 'local_railvieweradmin'); ?></th>
            <th><?php echo get_string('is_static', 'local_railvieweradmin'); ?></th>
            <th><?php echo get_string('actions', 'local_railvieweradmin'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="4"><em><?php echo get_string('no_points_saved', 'local_railvieweradmin'); ?></em></td></tr>
        <?php else: ?>
            <?php foreach ($records as $index => $record): ?>
                <tr data-id="<?= $record->id?>" class="section-map-row" data-videotime="<?= $record->videotime ?>">

                    <td><?= round($record->videotime, 2) ?></td>
                    <td><?= round($record->line_top, 1) ?></td>
                    <td><?= $record->is_static ? get_string('yes', 'local_railvieweradmin') : get_string('no', 'local_railvieweradmin') ?></td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-point" data-id="<?= $record->id ?>"><?php echo get_string('delete', 'local_railvieweradmin'); ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<script>
// sesskey wird für den CSRF-Schutz bei ebula_section_map_delete.php benötigt.
const ebulaSectionMapSesskey = <?php echo json_encode(sesskey()); ?>;

function ebulaMapDeletePoint(id) {
    if (!confirm(<?php echo json_encode(get_string('confirm_delete', 'local_railvieweradmin')); ?>)) {
        return;
    }
    fetch('ebula_section_map_delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id, sesskey: ebulaSectionMapSesskey})
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) {
            reloadTable();
        } else {
            alert(<?php echo json_encode(get_string('delete_error', 'local_railvieweradmin')); ?> + ': ' + json.message);
        }
    });
}

document.querySelectorAll('.delete-point').forEach(btn => {
    btn.addEventListener('click', e => ebulaMapDeletePoint(e.target.getAttribute('data-id')));
});

function reloadTable() {
    fetch('<?php echo (new moodle_url("/local/railvieweradmin/ebula_section_map_table.php"))->out(); ?>?sectionid=' + sectionId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('section-map-table').innerHTML = html;

            // Eventlistener neu binden
            bindDeleteButtons();
        });
}

function bindDeleteButtons() {
    document.querySelectorAll('.delete-point').forEach(btn => {
        btn.addEventListener('click', e => ebulaMapDeletePoint(e.target.getAttribute('data-id')));
    });
}

</script>

