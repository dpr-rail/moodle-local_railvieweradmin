<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/streckenmap.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('streckenmap', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('streckenmap', 'local_railvieweradmin'));
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('streckenmap', 'local_railvieweradmin'));

// Cache-Busting anhand des Datei-Zeitstempels: ohne das behält der Browser eine geänderte
// .js/.css bis zum harten Reload im Cache (kein Moodle-Theme-Revision-Mechanismus greift hier,
// da es sich um plain <script src="...">-Tags außerhalb des Theme-CSS handelt).
$assetversion = function (string $relpath): moodle_url {
    $fullpath = __DIR__ . $relpath;
    $rev = is_file($fullpath) ? filemtime($fullpath) : 0;
    return new moodle_url('/local/railvieweradmin' . $relpath, ['v' => $rev]);
};

// Muss vor $OUTPUT->header() passieren - Moodle druckt <head> (inkl. aller CSS-Requires)
// bereits beim header()-Aufruf, danach nachträglich requires->css() aufzurufen wirft
// "Cannot require a CSS file after <head> has been printed."
$PAGE->requires->css($assetversion('/thirdpartylibs/leaflet/leaflet.css'));
$PAGE->requires->css($assetversion('/streckenmap/style.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('streckenmap', 'local_railvieweradmin'));

if (empty(get_config('local_railvieweradmin', 'map_enabled'))) {
    echo $OUTPUT->notification(get_string('map_disabled_notice', 'local_railvieweradmin'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$tileurl = get_config('local_railvieweradmin', 'map_tile_url');
if (empty($tileurl)) {
    echo $OUTPUT->notification(get_string('map_no_tileurl_notice', 'local_railvieweradmin'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->footer();
    exit;
}

$tileattribution = get_config('local_railvieweradmin', 'map_tile_attribution');
$tilemaxzoom = (int) get_config('local_railvieweradmin', 'map_tile_max_zoom') ?: 19;

echo html_writer::tag('p', get_string('streckenmap_admin_intro', 'local_railvieweradmin'), ['class' => 'railviewer-admin-lead']);

global $DB;

// Bereits geo-verknüpfte Abschnitte laden, um sie in der Karte als
// "zugeordnet" zu markieren.
$geosections = $DB->get_records_select('railviewer_sections', "location_type IS NOT NULL AND location_type <> ''");
$chainassignments = [];
$nodeassignments = [];
foreach ($geosections as $section) {
    $assignment = [
        'sectionid' => (int) $section->id,
        'title' => $section->name,
        'url' => (new moodle_url('/local/railvieweradmin/edit_section.php', ['id' => $section->id]))->out(false),
    ];
    if ($section->location_type === 'edge') {
        $edges = $DB->get_records('railviewer_section_edges', ['sectionid' => $section->id], 'sortorder ASC');
        $edgerefs = array_map(function($e) {
            return ['edge_id' => $e->edge_id, 'direction' => $e->direction];
        }, array_values($edges));
        if ($edgerefs) {
            $chainassignments[] = ['edges' => $edgerefs, 'assignment' => $assignment];
        }
    } else if ($section->location_type === 'node' && !empty($section->node_ril100)) {
        $nodeassignments[] = ['ril100' => $section->node_ril100, 'assignment' => $assignment];
    }
}

?>
<div class="railviewer-map-layout">
  <div id="railviewer-streckenmap" class="railviewer-map-canvas"></div>
  <div class="railviewer-map-sidebar">
    <h3><?php echo get_string('streckenmap_selection_heading', 'local_railvieweradmin'); ?></h3>
    <p class="hint"><?php echo get_string('streckenmap_admin_hint', 'local_railvieweradmin'); ?></p>

    <div id="railviewer-chain-panel" class="railviewer-map-chain-info" style="display:none;">
      <strong><?php echo get_string('streckenmap_current_chain', 'local_railvieweradmin'); ?></strong>
      <ol id="railviewer-chain-list"></ol>
      <div class="railviewer-map-chain-actions">
        <button type="button" id="railviewer-chain-remove-last" class="btn btn-secondary"><?php echo get_string('streckenmap_remove_last', 'local_railvieweradmin'); ?></button>
        <button type="button" id="railviewer-chain-clear" class="btn btn-secondary"><?php echo get_string('streckenmap_discard_chain', 'local_railvieweradmin'); ?></button>
        <button type="button" id="railviewer-chain-continue" class="btn btn-primary"><?php echo get_string('streckenmap_continue', 'local_railvieweradmin'); ?></button>
      </div>
    </div>

    <div id="railviewer-node-panel" class="railviewer-map-chain-info" style="display:none;">
      <strong><?php echo get_string('streckenmap_node_selected', 'local_railvieweradmin'); ?></strong>
      <p id="railviewer-node-name"></p>
      <button type="button" id="railviewer-node-continue" class="btn btn-primary"><?php echo get_string('streckenmap_continue', 'local_railvieweradmin'); ?></button>
    </div>

    <div class="railviewer-map-legend">
      <div class="railviewer-map-legend-item"><span class="railviewer-map-legend-swatch" style="background:#9aa0a6;"></span><?php echo get_string('streckenmap_legend_unassigned', 'local_railvieweradmin'); ?></div>
      <div class="railviewer-map-legend-item"><span class="railviewer-map-legend-swatch" style="background:#6a3df5;"></span><?php echo get_string('streckenmap_legend_assigned', 'local_railvieweradmin'); ?></div>
      <div class="railviewer-map-legend-item"><span class="railviewer-map-legend-swatch" style="background:#ff0055;"></span><?php echo get_string('streckenmap_legend_selected', 'local_railvieweradmin'); ?></div>
    </div>
  </div>
</div>

<script src="<?php echo $assetversion('/thirdpartylibs/leaflet/leaflet.js')->out(false); ?>"></script>
<script src="<?php echo $assetversion('/streckenmap/offset-lines.js')->out(false); ?>"></script>
<script src="<?php echo $assetversion('/streckenmap/map-widget.js')->out(false); ?>"></script>
<script>
(function() {
    const wwwroot = <?php echo json_encode($CFG->wwwroot); ?>;
    const labels = {
        hinfahrt: <?php echo json_encode(get_string('direction_hin', 'local_railvieweradmin')); ?>,
        rueckfahrt: <?php echo json_encode(get_string('direction_rueck', 'local_railvieweradmin')); ?>,
        strecke: <?php echo json_encode(get_string('route_label_short', 'local_railvieweradmin')); ?>,
        ortskenntnis: <?php echo json_encode(get_string('ortskenntnis', 'local_railvieweradmin')); ?>,
    };
    const chainAssignments = <?php echo json_encode($chainassignments); ?>;
    const nodeAssignments = <?php echo json_encode($nodeassignments); ?>;

    const widget = new StreckenMapWidget('railviewer-streckenmap', {
        mode: 'admin',
        tileUrl: <?php echo json_encode($tileurl); ?>,
        tileAttribution: <?php echo json_encode($tileattribution); ?>,
        maxZoom: <?php echo (int) $tilemaxzoom; ?>,
        labels: labels,
    });

    function loadJson(url) {
        return fetch(url, {credentials: 'same-origin'}).then(function(r) { return r.json(); });
    }

    Promise.all([
        loadJson(wwwroot + '/local/railvieweradmin/ajax/get_edges.php'),
        loadJson(wwwroot + '/local/railvieweradmin/ajax/get_nodes.php'),
    ]).then(function(results) {
        widget.loadEdges(results[0]);
        widget.loadNodes(results[1]);

        chainAssignments.forEach(function(item) {
            widget.setChainAssignment(item.edges, item.assignment);
        });
        nodeAssignments.forEach(function(item) {
            widget.setNodeAssignment(item.ril100, item.assignment);
        });
    }).catch(function(err) {
        // Echte Fehlermeldung mit ausgeben statt nur den generischen Text -
        // ohne das war beim Debuggen nicht erkennbar, was tatsächlich fehlschlägt
        // (Netzwerk/Fetch, JSON-Parsing oder ein Fehler in loadEdges/loadNodes).
        window.console && console.error('Streckenkarte: Laden fehlgeschlagen', err);
        var detail = err && err.message ? err.message : String(err);
        document.getElementById('railviewer-streckenmap').innerHTML =
            '<div class="alert alert-danger"><?php echo get_string('generic_ajax_error', 'local_railvieweradmin'); ?><br><small>' +
            detail.replace(/[<>&]/g, function(c) { return {'<':'&lt;','>':'&gt;','&':'&amp;'}[c]; }) +
            '</small></div>';
    });

    const chainPanel = document.getElementById('railviewer-chain-panel');
    const chainList = document.getElementById('railviewer-chain-list');
    const nodePanel = document.getElementById('railviewer-node-panel');
    const nodeNameEl = document.getElementById('railviewer-node-name');
    let currentNodeFeature = null;

    widget.onChainChange = function(chain) {
        if (!chain.length) {
            chainPanel.style.display = 'none';
            chainList.innerHTML = '';
            return;
        }
        chainPanel.style.display = 'block';
        chainList.innerHTML = '';
        chain.forEach(function(link) {
            const li = document.createElement('li');
            const dirLabel = link.direction === 'hin' ? labels.hinfahrt : labels.rueckfahrt;
            li.textContent = link.von_ril100 + ' → ' + link.bis_ril100 + ' (' + labels.strecke + ' ' + link.streckennummer + ', ' + dirLabel + ')';
            chainList.appendChild(li);
        });
    };

    widget.onNodeSelect = function(nodeFeature) {
        currentNodeFeature = nodeFeature;
        nodePanel.style.display = 'block';
        nodeNameEl.textContent = nodeFeature.properties.name + ' (' + nodeFeature.properties.ril100 + ')';
    };

    document.getElementById('railviewer-chain-remove-last').addEventListener('click', function() {
        widget.removeLastFromChain();
    });
    document.getElementById('railviewer-chain-clear').addEventListener('click', function() {
        widget.clearChain();
    });

    document.getElementById('railviewer-chain-continue').addEventListener('click', function() {
        const chain = widget.getCurrentChain();
        if (!chain.length) return;
        const edges = chain.map(function(link) {
            return {edge_id: link.edge_id, direction: link.direction};
        });
        fetch(wwwroot + '/local/railvieweradmin/ajax/get_section_by_edges.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({edges: edges}),
        }).then(function(r) { return r.json(); }).then(function(response) {
            if (response.status === 'exists') {
                window.location.href = response.url;
            } else if (response.status === 'new') {
                const first = chain[0];
                const last = chain[chain.length - 1];
                const suggestedname = first.von_ril100 + ' ↔ ' + last.bis_ril100;
                const params = new URLSearchParams();
                params.set('location_type', 'edge');
                params.set('chain', JSON.stringify(chain));
                params.set('suggestedname', suggestedname);
                window.location.href = wwwroot + '/local/railvieweradmin/edit_section.php?' + params.toString();
            } else {
                alert(<?php echo json_encode(get_string('streckenmap_chain_conflict', 'local_railvieweradmin')); ?>);
            }
        });
    });

    document.getElementById('railviewer-node-continue').addEventListener('click', function() {
        if (!currentNodeFeature) return;
        const ril100 = currentNodeFeature.properties.ril100;
        fetch(wwwroot + '/local/railvieweradmin/ajax/get_section_by_node.php?ril100=' + encodeURIComponent(ril100), {
            credentials: 'same-origin',
        }).then(function(r) { return r.json(); }).then(function(response) {
            if (response.status === 'exists') {
                window.location.href = response.url;
            } else {
                const params = new URLSearchParams();
                params.set('location_type', 'node');
                params.set('node_ril100', ril100);
                params.set('node_name', currentNodeFeature.properties.name);
                params.set('suggestedname', <?php echo json_encode(get_string('ortskenntnis', 'local_railvieweradmin')); ?> + ' ' + currentNodeFeature.properties.name);
                window.location.href = wwwroot + '/local/railvieweradmin/edit_section.php?' + params.toString();
            }
        });
    });
})();
</script>
<?php

echo $OUTPUT->footer();
