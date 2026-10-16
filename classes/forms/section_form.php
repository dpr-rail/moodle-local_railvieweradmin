<?php
namespace local_railvieweradmin\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class section_form extends \moodleform {

    public function definition() {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/local/railvieweradmin/lib.php');

        $mform = $this->_form;

        // Hidden ID field
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Wird per JS beim Auswählen eines Vimeo-/ignite.video-Videos aus dessen vom Anbieter
        // gemeldeter Dauer befüllt (siehe add_javascript()/selectVideo()) - dient in play.php
        // als Fallback, falls der eingebettete Player die Dauer beim Abspielen nicht live
        // ermitteln kann.
        $mform->addElement('hidden', 'video_duration');
        $mform->setType('video_duration', PARAM_INT);

        // Geografische Verknüpfung (Streckenkarten-Widget): wird ausschließlich über die
        // Kartenseite (streckenmap.php) gesetzt, hier nur transportiert/angezeigt, nicht
        // direkt editierbar. location_type bleibt bei rein namensbasierten Bestandsabschnitten
        // leer.
        $mform->addElement('hidden', 'location_type');
        $mform->setType('location_type', PARAM_ALPHA);
        $mform->addElement('hidden', 'node_ril100');
        $mform->setType('node_ril100', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'node_name');
        $mform->setType('node_name', PARAM_TEXT);
        // Kanten-Kette einer NEUEN, per Kartenklick angelegten Sektion (JSON-kodiert). Wird
        // nur beim allerersten Speichern eines von der Karte erstellten Abschnitts ausgewertet
        // (siehe edit_section.php) - bei jedem weiteren Bearbeiten dieses Abschnitts bleibt das
        // Feld leer und die einmal gespeicherten railviewer_section_edges-Zeilen unangetastet.
        $mform->addElement('hidden', 'chain_json');
        $mform->setType('chain_json', PARAM_RAW);

        $locationtype = $this->_customdata['data']->location_type ?? '';
        if ($locationtype === 'edge') {
            $edgecount = $this->_customdata['edgecount'] ?? 0;
            $mform->addElement('static', 'location_info', get_string('streckenmap_linked', 'local_railvieweradmin'),
                get_string('streckenmap_linked_edges', 'local_railvieweradmin', $edgecount));
        } else if ($locationtype === 'node') {
            $nodename = $this->_customdata['data']->node_name ?? '';
            $mform->addElement('static', 'location_info', get_string('streckenmap_linked', 'local_railvieweradmin'),
                get_string('streckenmap_linked_node', 'local_railvieweradmin', format_string($nodename)));
        }

        // Name field
        $mform->addElement('text', 'name', get_string('name', 'local_railvieweradmin'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required_field', 'local_railvieweradmin'), 'required', null, 'client');

        // Description field
        $mform->addElement('textarea', 'description', get_string('description', 'local_railvieweradmin'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Video Source Selection - nur Quellen anbieten, die auch tatsächlich in den Plugin-
        // Settings eingerichtet sind (siehe local_railvieweradmin_get_configured_video_sources());
        // eine anwählbare, aber mangels Zugangsdaten gar nicht funktionsfähige Option würde nur
        // verwirren. Ausnahme: Ein bereits bestehender Abschnitt behält seine aktuelle Quelle in
        // der Liste, auch wenn deren Konfiguration inzwischen entfernt wurde - sonst würde allein
        // das Öffnen des Bearbeiten-Formulars die gespeicherte Auswahl unsichtbar machen bzw. das
        // Formular beim Speichern stillschweigend auf eine andere Quelle umstellen.
        $alllabels = [
            'upload' => get_string('upload_file', 'local_railvieweradmin'),
            'vimeo' => get_string('select_vimeo_video', 'local_railvieweradmin'),
            'ignite' => get_string('select_ignite_video', 'local_railvieweradmin'),
            'sharepoint' => get_string('select_sharepoint_video', 'local_railvieweradmin'),
            's3' => get_string('select_s3_video', 'local_railvieweradmin'),
        ];
        $configuredsources = local_railvieweradmin_get_configured_video_sources();
        $currentsource = $this->_customdata['data']->video_source ?? null;

        $video_sources = [];
        foreach ($alllabels as $key => $label) {
            $isconfigured = !empty($configuredsources[$key]);
            if (!$isconfigured && $currentsource !== $key) {
                continue;
            }
            if (!$isconfigured) {
                $label .= ' ' . get_string('video_source_not_configured_suffix', 'local_railvieweradmin');
            }
            $video_sources[$key] = $label;
        }
        $mform->addElement('select', 'video_source', get_string('video_source', 'local_railvieweradmin'), $video_sources);
        $mform->setDefault('video_source', 'upload');

        // Video Upload (nur bei Upload-Quelle sichtbar)
        $mform->addElement('filepicker', 'videofile', get_string('upload_video_file', 'local_railvieweradmin'), null, [
            'accepted_types' => ['video'],
            'maxfiles' => 1,
            'maxbytes' => 104857600, // 100 MB
        ]);
        $mform->hideIf('videofile', 'video_source', 'neq', 'upload');

        // Video-Auswahl für externe Anbieter (nur bei entsprechender Quelle sichtbar)
        $this->add_external_video_selector($mform, 'vimeo', 'select_vimeo_video_label');
        $this->add_external_video_selector($mform, 'ignite', 'select_ignite_video_label');
        // SharePoint bietet (anders als Vimeo/ignite) keinen direkten Upload-per-Signed-URL-
        // Endpunkt an, den man ohne mehrteilige Resumable-Upload-Sitzung von einem einzelnen
        // Browser-PUT aus ansprechen könnte - deshalb hier nur der Such-/Auswahl-Modus
        // ($allowupload = false), kein Umschalter/Upload-Panel.
        $this->add_external_video_selector($mform, 'sharepoint', 'select_sharepoint_video_label', false);

        // S3 Object Storage (nur bei S3-Quelle sichtbar)
        $this->add_s3_selector($mform);

        $this->add_action_buttons();

        // JavaScript für dynamische Anzeige hinzufügen
        $this->add_javascript();
    }

    /**
     * Baut die Auswahl-UI für eine externe Videoquelle (Vimeo, ignite.video, ...) auf.
     * Beide Anbieter werden über dieselbe generische JS-Logik bedient (siehe add_javascript()),
     * die per Präfix/Ajax-Endpunkt parametrisiert wird - vermeidet doppelten Code je Anbieter.
     *
     * @param string $prefix z.B. 'vimeo' oder 'ignite' - wird für Feld-/Element-IDs verwendet
     * @param string $labelstring Sprachstring-Key für das Auswahl-Label
     * @param bool $allowupload Ob ein Umschalter + Panel für einen Direkt-Upload zu diesem
     *   Anbieter angeboten wird - nicht jeder Anbieter unterstützt das (siehe SharePoint-Aufruf).
     */
    private function add_external_video_selector($mform, $prefix, $labelstring, $allowupload = true) {
        // Status/Sichtbarkeits-Optionen beim Hochladen unterscheiden sich je Anbieter (Vimeos
        // eigene privacy.view-Werte vs. ignite.videos visibility).
        if ($prefix === 'vimeo') {
            $statusoptions = [
                'anybody' => get_string('upload_status_public', 'local_railvieweradmin'),
                'unlisted' => get_string('upload_status_unlisted', 'local_railvieweradmin'),
                'nobody' => get_string('upload_status_private', 'local_railvieweradmin'),
            ];
        } else {
            $statusoptions = [
                'public' => get_string('upload_status_public', 'local_railvieweradmin'),
                'private' => get_string('upload_status_private', 'local_railvieweradmin'),
            ];
        }
        $statusoptionshtml = '';
        foreach ($statusoptions as $value => $label) {
            $statusoptionshtml .= '<option value="' . $value . '">' . s($label) . '</option>';
        }

        $modetogglehtml = $allowupload ? '
                <div class="railviewer-video-mode-toggle mb-3">
                    <button type="button" class="btn btn-secondary railviewer-mode-btn active" data-prefix="' . $prefix . '" data-mode="search">' . get_string('mode_search', 'local_railvieweradmin') . '</button>
                    <button type="button" class="btn btn-outline-secondary railviewer-mode-btn" data-prefix="' . $prefix . '" data-mode="upload">' . get_string('mode_upload', 'local_railvieweradmin') . '</button>
                </div>' : '';

        $uploadpanelhtml = $allowupload ? '
                <div id="' . $prefix . '-upload-panel" style="display:none;">
                    <p class="text-muted small">' . get_string('upload_size_hint', 'local_railvieweradmin') . '</p>
                    <div class="form-group">
                        <label>' . get_string('upload_title', 'local_railvieweradmin') . '</label>
                        <input type="text" id="' . $prefix . '-upload-title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>' . get_string('description', 'local_railvieweradmin') . '</label>
                        <textarea id="' . $prefix . '-upload-description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>' . get_string('upload_status', 'local_railvieweradmin') . '</label>
                        <select id="' . $prefix . '-upload-status" class="form-control">' . $statusoptionshtml . '</select>' .
                        ($prefix === 'ignite' ? '<p class="text-muted small">' . get_string('ignite_publish_permission_hint', 'local_railvieweradmin') . '</p>' : '') . '
                    </div>
                    <div class="form-group">
                        <label>' . get_string('upload_file', 'local_railvieweradmin') . '</label>
                        <input type="file" id="' . $prefix . '-upload-file" class="form-control" accept="video/*">
                    </div>
                    <button type="button" id="' . $prefix . '-upload-submit" class="btn btn-primary">' . get_string('upload_start', 'local_railvieweradmin') . '</button>
                    <div id="' . $prefix . '-upload-result" style="margin-top: 10px;"></div>
                </div>' : '';

        $html = '
        <div id="' . $prefix . '-selector-container" style="display:none;">
            <div class="railviewer-video-selector-wrap">
                <label>' . get_string($labelstring, 'local_railvieweradmin') . '</label>
' . $modetogglehtml . '
                <div id="' . $prefix . '-search-panel">
                    <div class="' . $prefix . '-controls mb-3">
                        <input type="text" id="' . $prefix . '-search" class="form-control" placeholder="' . get_string('search_videos', 'local_railvieweradmin') . '" style="margin-bottom: 10px;">
                        <button type="button" id="load-' . $prefix . '-videos" class="btn btn-secondary">' . get_string('load_videos', 'local_railvieweradmin') . '</button>
                        <button type="button" id="refresh-' . $prefix . '-videos" class="btn btn-outline-secondary">' . get_string('refresh_videos', 'local_railvieweradmin') . '</button>
                    </div>
                    <div id="' . $prefix . '-loading" style="display:none;">
                        <i class="fa fa-spinner fa-spin"></i> ' . get_string('loading_videos', 'local_railvieweradmin') . '
                    </div>
                    <div id="' . $prefix . '-video-list" class="vimeo-video-grid"></div>
                    <div id="' . $prefix . '-pagination" class="mt-3"></div>
                </div>
' . $uploadpanelhtml . '
            </div>
        </div>';

        $mform->addElement('hidden', $prefix . '_video_id');
        // SharePoints ID ist ein zusammengesetztes "driveId|itemId" (siehe sharepoint_api.php) -
        // Graph-IDs selbst enthalten oft "!" (z.B. "b!xxxxx...") und stets unser eigenes "|" als
        // Trenner. PARAM_ALPHANUMEXT (nur Buchstaben/Zahlen/"_-") würde beides beim Absenden
        // stillschweigend herausfiltern und die ID kaputt zusammenkleben - anders als Vimeos rein
        // numerische bzw. ignites einfache alphanumerische ID, für die PARAM_ALPHANUMEXT reicht.
        $mform->setType($prefix . '_video_id', $prefix === 'sharepoint' ? PARAM_RAW_TRIMMED : PARAM_ALPHANUMEXT);

        $mform->addElement('static', $prefix . '_selector', '', $html);
        $mform->hideIf($prefix . '_selector', 'video_source', 'neq', $prefix);

        $selectedhtml = '<div id="selected-' . $prefix . '-video" style="display:none;"></div>';
        $mform->addElement('static', 'selected_' . $prefix . '_display', '', $selectedhtml);
        $mform->hideIf('selected_' . $prefix . '_display', 'video_source', 'neq', $prefix);
    }

    private function add_s3_selector($mform) {
        $html = '<div id="s3-selector-container">'
            . '<label>' . get_string('s3_select_label', 'local_railvieweradmin') . '</label>'
            . '<input type="text" id="s3-prefix" class="form-control mb-2" placeholder="' . s(get_string('s3_filter_prefix', 'local_railvieweradmin')) . '">'
            . '<select id="s3-object-list" class="form-control mb-2" size="8"></select>'
            . '<button type="button" id="s3-load-more" class="btn btn-outline-secondary mb-3" style="display:none;">' . get_string('s3_load_more', 'local_railvieweradmin') . '</button>'
            . '<div><label>' . get_string('s3_upload_label', 'local_railvieweradmin') . '</label>'
            . '<input type="file" id="s3-upload-file" class="form-control-file d-block" accept="video/*">'
            . '<progress id="s3-upload-progress" value="0" max="100" style="display:none;width:100%;"></progress></div>'
            . '<div id="s3-status" class="mt-2"></div>'
            . '</div>';
        $mform->addElement('hidden', 's3_object_key');
        $mform->setType('s3_object_key', PARAM_RAW_TRIMMED);
        $mform->addElement('static', 's3_selector', '', $html);
        $mform->hideIf('s3_selector', 'video_source', 'neq', 's3');
    }

    private function add_s3_javascript() {
        global $PAGE;
        $strings = json_encode([
            'loadfailed' => get_string('s3_request_failed', 'local_railvieweradmin', ''),
            'uploading' => get_string('s3_uploading', 'local_railvieweradmin'),
            'uploadfailed' => get_string('s3_upload_failed', 'local_railvieweradmin'),
            'selected' => get_string('s3_selected', 'local_railvieweradmin'),
        ]);
        $PAGE->requires->js_init_code('
            window.addEventListener("load", function() {
                var L = ' . $strings . ';
                var base = M.cfg.wwwroot + "/local/railvieweradmin/ajax/";
                var list = document.getElementById("s3-object-list");
                if (!list) { return; }
                var hidden = document.querySelector("input[name=s3_object_key]");
                var status = document.getElementById("s3-status");
                var more = document.getElementById("s3-load-more");
                var prefix = document.getElementById("s3-prefix");
                var token = "", loaded = false, timer = null;

                function show(key) {
                    status.textContent = key ? L.selected + ": " + key : "";
                    status.className = key ? "mt-2 alert alert-success" : "mt-2";
                }
                function addOption(key, size, select) {
                    var o = document.createElement("option");
                    o.value = key;
                    o.textContent = key + (size ? " (" + (size / 1048576).toFixed(1) + " MB)" : "");
                    list.appendChild(o);
                    if (select) { o.selected = true; }
                }
                function load(append) {
                    if (!append) { list.innerHTML = ""; token = ""; }
                    fetch(base + "get_s3_videos.php?sesskey=" + M.cfg.sesskey + "&prefix=" + encodeURIComponent(prefix.value) + "&token=" + encodeURIComponent(token))
                        .then(function(r) { return r.json(); })
                        .then(function(d) {
                            if (!d.success) { status.className = "mt-2 alert alert-danger"; status.textContent = d.error; return; }
                            d.objects.forEach(function(o) { addOption(o.key, o.size, o.key === hidden.value); });
                            token = d.next || "";
                            more.style.display = token ? "inline-block" : "none";
                            loaded = true;
                        })
                        .catch(function(e) { status.className = "mt-2 alert alert-danger"; status.textContent = e; });
                }
                list.addEventListener("change", function() { hidden.value = list.value; show(list.value); });
                more.addEventListener("click", function() { load(true); });
                prefix.addEventListener("input", function() { clearTimeout(timer); timer = setTimeout(function() { load(false); }, 400); });

                document.getElementById("s3-upload-file").addEventListener("change", function() {
                    var file = this.files[0];
                    if (!file) { return; }
                    var bar = document.getElementById("s3-upload-progress");
                    status.className = "mt-2"; status.textContent = L.uploading;
                    fetch(base + "s3_presign_upload.php?sesskey=" + M.cfg.sesskey + "&filename=" + encodeURIComponent(file.name))
                        .then(function(r) { return r.json(); })
                        .then(function(d) {
                            if (!d.success) { throw new Error(d.error); }
                            return new Promise(function(resolve, reject) {
                                var xhr = new XMLHttpRequest();
                                xhr.open("PUT", d.url);
                                bar.style.display = "block";
                                xhr.upload.onprogress = function(e) { if (e.lengthComputable) { bar.value = e.loaded / e.total * 100; } };
                                xhr.onload = function() { xhr.status >= 200 && xhr.status < 300 ? resolve(d.key) : reject(new Error(L.uploadfailed)); };
                                xhr.onerror = function() { reject(new Error(L.uploadfailed)); };
                                xhr.send(file);
                            });
                        })
                        .then(function(key) {
                            hidden.value = key; show(key);
                            addOption(key, file.size, true);
                            bar.style.display = "none";
                        })
                        .catch(function(e) { bar.style.display = "none"; status.className = "mt-2 alert alert-danger"; status.textContent = e.message; });
                });

                function onSource() {
                    var sel = document.getElementById("id_video_source");
                    if (sel && sel.value === "s3" && !loaded) { load(false); }
                }
                var src = document.getElementById("id_video_source");
                if (src) { src.addEventListener("change", onSource); }
                show(hidden.value);
                onSource();
            });
        ');
    }

    private function add_javascript() {
        $this->add_s3_javascript();
        global $PAGE;

        $providers = [
            'vimeo' => [
                'ajax' => 'get_vimeo_videos.php',
                'idlabel' => get_string('vimeo_id', 'local_railvieweradmin', '__ID__'),
                'createdlabel' => get_string('created', 'local_railvieweradmin', '__DATE__'),
            ],
            'ignite' => [
                'ajax' => 'get_ignite_videos.php',
                'idlabel' => get_string('ignite_id', 'local_railvieweradmin', '__ID__'),
                'createdlabel' => get_string('created', 'local_railvieweradmin', '__DATE__'),
            ],
            'sharepoint' => [
                'ajax' => 'get_sharepoint_videos.php',
                // Kein "__ID__"-Platzhalter: die SharePoint-ID ist eine technische
                // "driveId|itemId"-Kombination (siehe sharepoint_api.php), deren Rohanzeige für
                // den Admin nicht hilfreich wäre - anders als eine kurze Vimeo-/ignite-ID.
                'idlabel' => get_string('sharepoint_id', 'local_railvieweradmin'),
                'createdlabel' => get_string('created', 'local_railvieweradmin', '__DATE__'),
            ],
        ];

        $PAGE->requires->js_init_code('
            window.addEventListener("load", function() {
                var providers = ' . json_encode($providers) . ';
                var state = {};
                // Eigenes, in sich geschlossenes Platzhalterbild statt einer Datei unter pix/,
                // die es in diesem Plugin gar nicht gibt (pix/no-thumbnail.png existierte nie -
                // das kaputte <img> zeigte deshalb nur den Alt-Text statt eines Bildes an).
                var noThumbnailSvg = "data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' " +
                    "width=\'120\' height=\'68\' viewBox=\'0 0 120 68\'%3E%3Crect width=\'120\' height=\'68\' " +
                    "fill=\'%23dde1e6\'/%3E%3Cpath d=\'M46 22l30 12-30 12z\' fill=\'%239aa0a6\'/%3E%3C/svg%3E";

                Object.keys(providers).forEach(function(prefix) {
                    state[prefix] = {page: 1, search: "", selectedId: ""};
                });

                // Moodle steckt dieses "static"-Formularfeld in eine .form-inline-Zeile
                // (Bootstrap display:flex) - als Flex-Item schrumpft es standardmäßig auf seine
                // Inhaltsbreite statt die Zeile zu füllen. Ein CSS-Fix darauf per :has() griff
                // nicht zuverlässig (Browser-Unterstützung ungewiss, bei Nichtunterstützung wird
                // die GESAMTE Regel verworfen) - daher die betroffene Flex-Item-Zelle hier direkt
                // per JS markieren und in style.css nur noch auf diese eigene Klasse zielen.
                Object.keys(providers).forEach(function(prefix) {
                    var containerEl = document.getElementById(prefix + "-selector-container");
                    var wrap = containerEl && containerEl.closest(".form-control-static");
                    if (wrap) {
                        wrap.classList.add("railviewer-video-picker-field");
                    }
                });

                var videoSourceSelect = document.getElementById("id_video_source");
                if (videoSourceSelect) {
                    videoSourceSelect.addEventListener("change", function() {
                        var source = this.value;
                        Object.keys(providers).forEach(function(prefix) {
                            var container = document.getElementById(prefix + "-selector-container");
                            if (!container) return;
                            if (source === prefix) {
                                container.style.display = "block";
                                loadVideos(prefix);
                            } else {
                                container.style.display = "none";
                            }
                        });
                    });

                    Object.keys(providers).forEach(function(prefix) {
                        if (videoSourceSelect.value === prefix) {
                            var container = document.getElementById(prefix + "-selector-container");
                            if (container) {
                                container.style.display = "block";
                                loadVideos(prefix);
                            }
                        }

                        var existingId = document.getElementById("id_" + prefix + "_video_id");
                        if (existingId && existingId.value) {
                            state[prefix].selectedId = existingId.value;
                            showSelectedVideo(prefix, existingId.value, "' . get_string('already_selected', 'local_railvieweradmin') . '");
                        }

                        var loadBtn = document.getElementById("load-" + prefix + "-videos");
                        if (loadBtn) {
                            loadBtn.addEventListener("click", function() { loadVideos(prefix); });
                        }

                        var refreshBtn = document.getElementById("refresh-" + prefix + "-videos");
                        if (refreshBtn) {
                            refreshBtn.addEventListener("click", function() {
                                state[prefix].page = 1;
                                loadVideos(prefix);
                            });
                        }

                        var searchInput = document.getElementById(prefix + "-search");
                        if (searchInput) {
                            var searchTimeout;
                            searchInput.addEventListener("input", function() {
                                clearTimeout(searchTimeout);
                                state[prefix].search = this.value;
                                searchTimeout = setTimeout(function() {
                                    state[prefix].page = 1;
                                    loadVideos(prefix);
                                }, 500);
                            });
                        }

                        var searchPanel = document.getElementById(prefix + "-search-panel");
                        var uploadPanel = document.getElementById(prefix + "-upload-panel");
                        document.querySelectorAll(\'.railviewer-mode-btn[data-prefix="\' + prefix + \'"]\').forEach(function(modeBtn) {
                            modeBtn.addEventListener("click", function() {
                                var mode = this.getAttribute("data-mode");
                                document.querySelectorAll(\'.railviewer-mode-btn[data-prefix="\' + prefix + \'"]\').forEach(function(btn) {
                                    btn.classList.toggle("active", btn === modeBtn);
                                    btn.classList.toggle("btn-secondary", btn === modeBtn);
                                    btn.classList.toggle("btn-outline-secondary", btn !== modeBtn);
                                });
                                if (searchPanel) searchPanel.style.display = mode === "search" ? "block" : "none";
                                if (uploadPanel) uploadPanel.style.display = mode === "upload" ? "block" : "none";
                            });
                        });

                        var uploadBtn = document.getElementById(prefix + "-upload-submit");
                        if (uploadBtn) {
                            uploadBtn.addEventListener("click", function() { uploadVideo(prefix); });
                        }
                    });
                }

                // Große Videodateien (mehrere GB) gehen NIE über unseren PHP-Server - dessen
                // upload_max_filesize/post_max_size, Ausführungszeit und Speicher machen das für
                // solche Größen unbrauchbar, und ein einzelner abgebrochener Upload müsste sonst
                // komplett neu beginnen. Stattdessen: (1) kleiner Ajax-Call legt nur das Video-
                // Objekt beim Anbieter an und liefert ein Upload-Ziel zurück, (2) der Browser lädt
                // die Datei-Bytes DIREKT dorthin hoch (Vimeo: TUS-Protokoll in Chunks mit
                // Offset-Tracking, ignite: einzelner PUT an eine vorsignierte S3-URL).
                function uploadVideo(prefix) {
                    var titleInput = document.getElementById(prefix + "-upload-title");
                    var descriptionInput = document.getElementById(prefix + "-upload-description");
                    var statusSelect = document.getElementById(prefix + "-upload-status");
                    var fileInput = document.getElementById(prefix + "-upload-file");
                    var resultEl = document.getElementById(prefix + "-upload-result");
                    var uploadBtn = document.getElementById(prefix + "-upload-submit");
                    if (!titleInput || !fileInput || !resultEl) return;

                    resultEl.innerHTML = "";

                    var title = titleInput.value.trim();
                    if (!title) {
                        resultEl.innerHTML = "<div class=\\"alert alert-danger\\">' . get_string('upload_title_required', 'local_railvieweradmin') . '</div>";
                        return;
                    }
                    if (!fileInput.files || !fileInput.files.length) {
                        resultEl.innerHTML = "<div class=\\"alert alert-danger\\">' . get_string('upload_no_file', 'local_railvieweradmin') . '</div>";
                        return;
                    }
                    var file = fileInput.files[0];
                    var description = descriptionInput ? descriptionInput.value : "";
                    var status = statusSelect ? statusSelect.value : "";

                    uploadBtn.disabled = true;
                    resultEl.innerHTML = "<div class=\\"railviewer-upload-progress\\"><div class=\\"railviewer-upload-progress-bar\\" id=\\"" + prefix + "-upload-progress-bar\\"></div></div>" +
                        "<small id=\\"" + prefix + "-upload-status-text\\">' . get_string('upload_in_progress', 'local_railvieweradmin') . '</small>";

                    function setProgress(pct) {
                        var bar = document.getElementById(prefix + "-upload-progress-bar");
                        if (bar) bar.style.width = Math.max(0, Math.min(100, pct)) + "%";
                    }

                    function fail(message) {
                        uploadBtn.disabled = false;
                        resultEl.innerHTML = "<div class=\\"alert alert-danger\\">" + escapeHtml(message || "' . get_string('error_loading_videos', 'local_railvieweradmin') . '") + "</div>";
                    }

                    function succeed(videoId) {
                        uploadBtn.disabled = false;
                        resultEl.innerHTML = "<div class=\\"alert alert-success\\">' . get_string('upload_success', 'local_railvieweradmin') . '</div>";
                        // Frisch hochgeladenes Video sofort wie ein aus der Liste ausgewähltes
                        // behandeln - direkt in dieser Section verlinkbar, kein separater
                        // Zwischenschritt nötig. Dauer ist beim Upload noch unbekannt (Provider hat
                        // die Datei gerade erst erhalten) - 0, play.php ermittelt sie live.
                        selectVideo(prefix, videoId, title, "", 0);
                        titleInput.value = "";
                        if (descriptionInput) descriptionInput.value = "";
                        fileInput.value = "";
                    }

                    // Schritt 1: Video-Objekt beim Anbieter anlegen, Upload-Ziel anfordern.
                    var createData = new FormData();
                    createData.append("sesskey", M.cfg.sesskey);
                    createData.append("title", title);
                    createData.append("description", description);
                    // Vimeo erwartet "privacy", ignite "visibility" - beide Endpunkte lesen jeweils
                    // nur ihren eigenen Parameternamen, der andere wird schlicht ignoriert.
                    createData.append("privacy", status);
                    createData.append("visibility", status);
                    createData.append("filesize", file.size);

                    var createXhr = new XMLHttpRequest();
                    createXhr.open("POST", M.cfg.wwwroot + "/local/railvieweradmin/ajax/create_" + prefix + "_upload.php", true);
                    createXhr.onreadystatechange = function() {
                        if (createXhr.readyState !== 4) return;
                        var response;
                        try {
                            response = JSON.parse(createXhr.responseText);
                        } catch (e) {
                            fail("' . get_string('error_parsing_response', 'local_railvieweradmin') . '");
                            return;
                        }
                        if (createXhr.status !== 200 || !response.success) {
                            fail(response.message);
                            return;
                        }

                        // Schritt 2: Datei-Bytes direkt vom Browser an den Anbieter hochladen.
                        if (prefix === "vimeo") {
                            tusUpload(response.upload_link, file, setProgress, function() {
                                succeed(response.id);
                            }, fail);
                        } else {
                            directPutUpload(response.upload_url, file, setProgress, function() {
                                succeed(response.id);
                            }, fail);
                        }
                    };
                    createXhr.send(createData);
                }

                // Einfacher, direkter Datei-Upload per PUT an eine (typischerweise vorsignierte)
                // URL - für ignite.videos S3-Upload-Ziel. Kein Chunking/Resume nötig: ein PUT-
                // Objekt-Upload zu S3 unterstützt in einem Rutsch bis zu 5 GB; für größere Dateien
                // müsste ignite.video eine mehrteilige (multipart) Upload-URL anbieten, was die
                // aktuelle API nicht tut.
                function directPutUpload(url, file, onProgress, onDone, onError) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("PUT", url, true);
                    xhr.setRequestHeader("Content-Type", file.type || "video/mp4");
                    xhr.upload.addEventListener("progress", function(e) {
                        if (e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 100));
                    });
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                onDone();
                            } else {
                                onError("File upload failed (HTTP " + xhr.status + ")");
                            }
                        }
                    };
                    xhr.onerror = function() {
                        onError("' . get_string('upload_network_error', 'local_railvieweradmin') . '");
                    };
                    xhr.send(file);
                }

                // Resumable TUS-Upload direkt an Vimeos Upload-Link (developer.vimeo.com/api/
                // upload/videos#uploading-a-file-tus). Sendet die Datei in Chunks statt in einem
                // einzigen Request - hält jede einzelne Anfrage klein/beherrschbar und erlaubt bei
                // einem Netzwerkfehler einen Wiederholungsversuch je Chunk, statt bei mehreren GB
                // komplett von vorn beginnen zu müssen.
                function tusUpload(uploadLink, file, onProgress, onDone, onError) {
                    var chunkSize = 50 * 1024 * 1024; // 50 MB je Chunk
                    var offset = 0;
                    var retriesLeft = 3;

                    function sendChunk() {
                        var end = Math.min(offset + chunkSize, file.size);
                        var chunk = file.slice(offset, end);
                        var chunkStart = offset;

                        var xhr = new XMLHttpRequest();
                        xhr.open("PATCH", uploadLink, true);
                        xhr.setRequestHeader("Tus-Resumable", "1.0.0");
                        xhr.setRequestHeader("Upload-Offset", String(offset));
                        xhr.setRequestHeader("Content-Type", "application/offset+octet-stream");
                        xhr.upload.addEventListener("progress", function(e) {
                            if (e.lengthComputable) {
                                onProgress(Math.round(((chunkStart + e.loaded) / file.size) * 100));
                            }
                        });
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState !== 4) return;
                            if (xhr.status >= 200 && xhr.status < 300) {
                                retriesLeft = 3;
                                var reportedoffset = parseInt(xhr.getResponseHeader("Upload-Offset"), 10);
                                offset = isNaN(reportedoffset) ? end : reportedoffset;
                                if (offset >= file.size) {
                                    onDone();
                                } else {
                                    sendChunk();
                                }
                            } else if (retriesLeft > 0) {
                                retriesLeft--;
                                sendChunk();
                            } else {
                                onError("File upload failed (HTTP " + xhr.status + ")");
                            }
                        };
                        xhr.onerror = function() {
                            if (retriesLeft > 0) {
                                retriesLeft--;
                                sendChunk();
                            } else {
                                onError("' . get_string('upload_network_error', 'local_railvieweradmin') . '");
                            }
                        };
                        xhr.send(chunk);
                    }

                    sendChunk();
                }

                function loadVideos(prefix) {
                    var loading = document.getElementById(prefix + "-loading");
                    var videoList = document.getElementById(prefix + "-video-list");
                    if (!loading || !videoList) return;

                    loading.style.display = "block";
                    videoList.innerHTML = "";

                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", M.cfg.wwwroot + "/local/railvieweradmin/ajax/" + providers[prefix].ajax +
                        "?page=" + state[prefix].page + "&search=" + encodeURIComponent(state[prefix].search), true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            loading.style.display = "none";

                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        renderVideos(prefix, response.videos);
                                        renderPagination(prefix, response.total, response.page, response.per_page);
                                    } else {
                                        videoList.innerHTML = "<div class=\\"alert alert-danger\\">" + (response.message || "' . get_string('error_loading_videos', 'local_railvieweradmin') . '") + "</div>";
                                    }
                                } catch(e) {
                                    videoList.innerHTML = "<div class=\\"alert alert-danger\\">' . get_string('error_parsing_response', 'local_railvieweradmin') . '</div>";
                                }
                            } else {
                                videoList.innerHTML = "<div class=\\"alert alert-danger\\">' . get_string('error_loading_videos_status', 'local_railvieweradmin', '\" + xhr.status + \"') . '</div>";
                            }
                        }
                    };
                    xhr.send();
                }

                function escapeHtml(text) {
                    var div = document.createElement("div");
                    div.textContent = text == null ? "" : String(text);
                    return div.innerHTML;
                }

                function renderVideos(prefix, videos) {
                    var html = "";
                    videos.forEach(function(video) {
                        var isSelected = video.id === state[prefix].selectedId ? "selected" : "";
                        var name = escapeHtml(video.name);
                        html += "<div class=\\"vimeo-video-item " + isSelected + "\\" data-video-id=\\"" + escapeHtml(video.id) + "\\" data-duration=\\"" + escapeHtml(video.duration || 0) + "\\">";
                        html += "  <div class=\\"video-thumbnail\\">";
                        html += "    <img src=\\"" + escapeHtml(video.thumbnail || noThumbnailSvg) + "\\" alt=\\"" + name + "\\">";
                        html += "  </div>";
                        html += "  <div class=\\"video-info\\">";
                        html += "    <h5>" + name + "</h5>";
                        html += "    <p>" + (video.description ? escapeHtml(video.description.substring(0, 100)) + "..." : "' . get_string('no_description', 'local_railvieweradmin') . '") + "</p>";
                        html += "    <small>" + providers[prefix].createdlabel.replace("__DATE__", (video.created_time ? new Date(video.created_time).toLocaleDateString("de-DE") : "")) + "</small>";
                        html += "  </div>";
                        html += "  <div class=\\"video-actions\\">";
                        html += "    <button type=\\"button\\" class=\\"btn btn-primary select-video\\">' . get_string('select_video', 'local_railvieweradmin') . '</button>";
                        html += "  </div>";
                        html += "</div>";
                    });

                    var list = document.getElementById(prefix + "-video-list");
                    list.innerHTML = html;

                    list.querySelectorAll(".select-video").forEach(function(button) {
                        button.addEventListener("click", function() {
                            var videoItem = this.closest(".vimeo-video-item");
                            var videoId = videoItem.getAttribute("data-video-id");
                            var videoName = videoItem.querySelector("h5").textContent;
                            var videoThumbnail = videoItem.querySelector("img").src;
                            var videoDuration = videoItem.getAttribute("data-duration");
                            selectVideo(prefix, videoId, videoName, videoThumbnail, videoDuration);
                        });
                    });
                }

                function selectVideo(prefix, videoId, videoName, thumbnail, duration) {
                    state[prefix].selectedId = videoId;

                    var hiddenField = document.getElementById("id_" + prefix + "_video_id") ||
                                     document.querySelector(\'input[name="\' + prefix + \'_video_id"]\');
                    if (hiddenField) {
                        hiddenField.value = videoId;
                    }

                    // Vom Anbieter gemeldete Dauer serverseitig als Fallback speichern - falls
                    // der Player beim Abspielen die Dauer nicht live ermitteln kann, bleibt die
                    // Fortschrittsleiste sonst die ganze Wiedergabe über bei 0% hängen (siehe
                    // play.php, loadSection()).
                    var durationField = document.getElementById("id_video_duration") ||
                                     document.querySelector(\'input[name="video_duration"]\');
                    if (durationField && duration) {
                        durationField.value = Math.round(parseFloat(duration)) || 0;
                    }

                    document.querySelectorAll("#" + prefix + "-video-list .vimeo-video-item").forEach(function(item) {
                        item.classList.remove("selected");
                        if (item.getAttribute("data-video-id") === videoId) {
                            item.classList.add("selected");
                        }
                    });

                    showSelectedVideo(prefix, videoId, videoName, thumbnail);
                }

                function showSelectedVideo(prefix, videoId, videoName, thumbnail) {
                    var html = "<div class=\\"alert alert-success\\">";
                    html += "<strong>' . get_string('selected_video', 'local_railvieweradmin') . '</strong><br>";
                    if (thumbnail) {
                        html += "<img src=\\"" + escapeHtml(thumbnail) + "\\" style=\\"width: 120px; height: 68px; object-fit: cover; margin-right: 10px; float: left;\\">";
                    }
                    html += "<strong>" + escapeHtml(videoName) + "</strong><br>";
                    html += "<small>" + escapeHtml(providers[prefix].idlabel.replace("__ID__", videoId)) + "</small>";
                    html += "</div>";

                    var el = document.getElementById("selected-" + prefix + "-video");
                    el.innerHTML = html;
                    el.style.display = "block";
                }

                function renderPagination(prefix, total, page, perPage) {
                    var container = document.getElementById(prefix + "-pagination");
                    var totalPages = Math.ceil(total / perPage);
                    if (totalPages <= 1) {
                        container.innerHTML = "";
                        return;
                    }

                    var html = "<nav><ul class=\\"pagination\\">";
                    if (page > 1) {
                        html += "<li class=\\"page-item\\"><a class=\\"page-link\\" href=\\"#\\" data-page=\\"" + (page - 1) + "\\">' . get_string('previous', 'local_railvieweradmin') . '</a></li>";
                    }
                    for (var i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
                        var active = i === page ? "active" : "";
                        html += "<li class=\\"page-item " + active + "\\"><a class=\\"page-link\\" href=\\"#\\" data-page=\\"" + i + "\\">" + i + "</a></li>";
                    }
                    if (page < totalPages) {
                        html += "<li class=\\"page-item\\"><a class=\\"page-link\\" href=\\"#\\" data-page=\\"" + (page + 1) + "\\">' . get_string('next', 'local_railvieweradmin') . '</a></li>";
                    }
                    html += "</ul></nav>";
                    container.innerHTML = html;

                    container.querySelectorAll(".pagination a").forEach(function(link) {
                        link.addEventListener("click", function(e) {
                            e.preventDefault();
                            state[prefix].page = parseInt(this.getAttribute("data-page"));
                            loadVideos(prefix);
                        });
                    });
                }
            });
        ');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = get_string('name_required', 'local_railvieweradmin');
        }

        if ($data['video_source'] === 'vimeo' && empty($data['vimeo_video_id'])) {
            $errors['vimeo_video_id'] = get_string('vimeo_video_required', 'local_railvieweradmin');
        }

        if ($data['video_source'] === 'ignite' && empty($data['ignite_video_id'])) {
            $errors['ignite_video_id'] = get_string('ignite_video_required', 'local_railvieweradmin');
        }

        if ($data['video_source'] === 's3'
                && !\local_railvieweradmin\s3_client::is_valid_key($data['s3_object_key'] ?? '')) {
            $errors['s3_selector'] = get_string('s3_video_required', 'local_railvieweradmin');
        }

        if ($data['video_source'] === 'sharepoint' && empty($data['sharepoint_video_id'])) {
            $errors['sharepoint_video_id'] = get_string('sharepoint_video_required', 'local_railvieweradmin');
        }

        return $errors;
    }
}
