/**
 * map-widget.js
 * -----------------------------------------------------------------------
 * Karten-Komponente für die Streckenkarten-Ansicht von local_railvieweradmin
 * / mod_railviewer. Basiert auf dem Prototyp aus streckenvideo-map-widget/
 * (siehe dessen README.md), erweitert um:
 *
 *  - Knoten-/Marker-Rendering für einzelne Betriebsstellen ("Ortskenntnis"-
 *    Videos ohne Streckenbezug): loadNodes(), setNodeAssignment(),
 *    setNodeUserProgress().
 *  - Ketten-Bildung im Admin-Modus: ein Video kann mehrere zusammenhängende
 *    Kanten überspannen. Jeder Klick auf eine noch nicht in der aktuellen
 *    Kette enthaltene, an einem ihrer beiden Enden anschließende Kante
 *    verlängert die Kette; eine nicht angrenzende Kante beginnt eine neue.
 *  - Tooltip mit dem exakten Rest-Fortschritt im Nutzer-Modus, nicht nur
 *    Farbe.
 *  - Touch-/Tablet-Tauglichkeit: breite unsichtbare Trefferlinien statt der
 *    dünnen sichtbaren Linien, größere Knoten-Marker, Tooltip auch per Tap.
 *
 * Bewusst kein AMD-Modul (siehe Implementierungsplan) - wird wie die
 * übrigen Skripte dieses Plugins per <script src="..."> eingebunden.
 * -----------------------------------------------------------------------
 */

const STATUS_COLORS = {
  none: '#c9ccd1',           // kein Video hinterlegt / keine Aufgabe
  offen: '#3388ff',          // Video zugeordnet, noch nicht angesehen
  in_bearbeitung: '#f5a623', // teilweise angesehen (< erforderliche Views)
  erledigt: '#2ecc71',       // Pflichtanzahl Views erreicht
};

const ADMIN_COLORS = {
  unassigned: '#9aa0a6',
  assigned: '#6a3df5',
  selected: '#ff0055',
};

// Trefferbreite/-radius für Touch-Bedienung (siehe Implementierungsplan,
// Abschnitt "Touch-/Tablet-Tauglichkeit").
const HIT_LINE_WEIGHT = 20;
const NODE_MARKER_RADIUS = 11;
const NODE_MARKER_RADIUS_ACTIVE = 13;

const DEFAULT_LABELS = {
  hinfahrt: 'Hinfahrt',
  rueckfahrt: 'Rückfahrt',
  strecke: 'Strecke',
  ortskenntnis: 'Ortskenntnis',
  progress: '{views} von {required} Durchläufen – noch {remaining} offen',
  progressDone: '✓ abgeschlossen ({views}/{required})',
  noAssignment: '',
};

function fillTemplate(template, values) {
  return template.replace(/\{(\w+)\}/g, (match, key) => (key in values ? values[key] : match));
}

class StreckenMapWidget {
  /**
   * @param {string} elementId - id des Karten-<div>
   * @param {object} opts
   * @param {'admin'|'user'} opts.mode
   * @param {number} opts.offsetMeters - seitlicher Versatz Hin/Rueck-Linie
   *   INNERHALB einer Spur. Der Versatz ZWISCHEN parallelen Strecken
   *   (z.B. Fernbahn vs. S-Bahn) wird automatisch per assignLaneOffsets()
   *   berechnet (siehe offset-lines.js, LANE_SPACING_M).
   * @param {string} opts.tileUrl - Tile-Server-URL-Vorlage
   * @param {string} opts.tileAttribution - HTML-Attributionstext
   * @param {number} opts.maxZoom
   * @param {object} opts.labels - Textbausteine (Übersetzung), siehe DEFAULT_LABELS
   */
  constructor(elementId, opts = {}) {
    this.mode = opts.mode || 'user';
    this.offsetMeters = opts.offsetMeters || 12;
    this.labels = Object.assign({}, DEFAULT_LABELS, opts.labels || {});

    this.map = L.map(elementId, { preferCanvas: true, tap: true }).setView([51.2, 10.4], 6);
    L.tileLayer(opts.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: opts.tileAttribution || '&copy; OpenStreetMap contributors',
      maxZoom: opts.maxZoom || 19,
    }).addTo(this.map);

    // directionId -> { assignment, status, layer (sichtbar), hitLayer
    // (breite Trefferlinie), directionalLine, views }
    this.directionState = new Map();
    // ril100 -> { feature, layer, assignment, status, views }
    this.nodeState = new Map();
    // featureGroup statt layerGroup: nur FeatureGroup hat getBounds(), das
    // loadEdges() unten zum initialen Einpassen der Kartenansicht braucht.
    this.layerGroup = L.featureGroup().addTo(this.map);
    this.nodeLayerGroup = L.layerGroup().addTo(this.map);

    this.selectedDirectionId = null;
    this.onSectionSelect = null;   // callback(directionalLine) fuer admin (Einzelauswahl, legacy)
    this.onVideoOpen = null;       // callback(directionalLine, assignment) fuer user
    this.onNodeSelect = null;      // callback(nodeFeature) fuer admin
    this.onNodeVideoOpen = null;   // callback(nodeFeature, assignment) fuer user
    this.onChainChange = null;     // callback(chainArray) fuer admin, bei jeder Aenderung der Kette

    // Ketten-Zustand (Admin-Modus): geordnete Liste von Kettenglied-Objekten
    // { edge_id, direction, streckennummer, start, end, dlId }.
    this.currentChain = [];
  }

  // -------------------------------------------------------------------
  // Kanten (Streckenabschnitte)
  // -------------------------------------------------------------------

  /**
   * Laedt Kanten-GeoJSON, berechnet Hin/Rueck-Linien und zeichnet sie.
   * @param {object} edgesGeoJSON
   */
  loadEdges(edgesGeoJSON) {
    this.directionState.clear();
    this.layerGroup.clearLayers();
    this.currentChain = [];

    // Spur-Versatz je Kante ermitteln, damit parallele Strecken (z.B.
    // Fernbahn + S-Bahn zwischen denselben Betriebsstellen) nebeneinander
    // statt uebereinander liegen.
    const laneOffsets = assignLaneOffsets(edgesGeoJSON.features);

    edgesGeoJSON.features.forEach((feature) => {
      const laneOffset = laneOffsets.get(feature.properties.edge_id) || 0;
      const dirLines = buildDirectionalLines(feature, laneOffset, this.offsetMeters);
      dirLines.forEach((dl) => {
        const latlngs = dl.coords.map(([lon, lat]) => [lat, lon]);

        // Sichtbare, duenne Linie (nicht interaktiv - alle Klicks/Taps
        // laufen ueber die breitere Trefferlinie darueber).
        const layer = L.polyline(latlngs, Object.assign({ interactive: false }, this._styleFor(dl.id)));

        // Unsichtbare, breite Trefferlinie fuer verlaessliche Klicks/Taps
        // auch auf Tablets (duenne Linien sind sonst schwer zu treffen).
        const hitLayer = L.polyline(latlngs, {
          color: '#000000',
          opacity: 0,
          weight: HIT_LINE_WEIGHT,
        });
        hitLayer.on('click', (e) => {
          L.DomEvent.stopPropagation(e);
          hitLayer.openTooltip(e.latlng);
          this._handleEdgeClick(dl.id);
        });

        this.layerGroup.addLayer(layer);
        this.layerGroup.addLayer(hitLayer);

        this.directionState.set(dl.id, {
          directionalLine: dl,
          layer,
          hitLayer,
          assignment: null, // { videoUrl, requiredViews, title, sectionid, assignmentid }
          status: 'none',
          views: 0,
          inChain: false,
        });

        hitLayer.bindTooltip(this._tooltipFor(dl.id), { sticky: true });
      });
    });

    if (edgesGeoJSON.features.length) {
      const bounds = this.layerGroup.getBounds();
      if (bounds.isValid()) this.map.fitBounds(bounds, { padding: [20, 20] });
    }
  }

  _tooltipFor(directionId) {
    const entry = this.directionState.get(directionId);
    const dl = entry.directionalLine;
    const p = dl.props;
    const richtungLabel = dl.direction === 'hin' ? this.labels.hinfahrt : this.labels.rueckfahrt;
    let html = `${p.von_name} ↔ ${p.bis_name} (${richtungLabel})<br>${this.labels.strecke} ${p.streckennummer}, ${p.laenge_km} km`;
    if (this.mode === 'user' && entry.assignment) {
      html += '<br>' + this._progressText(entry);
    }
    return html;
  }

  _progressText(entry) {
    const required = entry.assignment.requiredViews;
    const views = entry.views;
    if (views >= required) {
      return fillTemplate(this.labels.progressDone, { views, required });
    }
    return fillTemplate(this.labels.progress, { views, required, remaining: Math.max(0, required - views) });
  }

  _styleFor(directionId) {
    const entry = this.directionState.get(directionId);
    let color;
    if (this.mode === 'admin') {
      if (entry && entry.inChain) {
        color = ADMIN_COLORS.selected;
      } else {
        color = entry && entry.assignment ? ADMIN_COLORS.assigned : ADMIN_COLORS.unassigned;
      }
    } else {
      const status = entry ? entry.status : 'none';
      color = STATUS_COLORS[status];
    }
    const isAssignedOrActive = this.mode === 'admin'
      ? (entry && (entry.assignment || entry.inChain))
      : (entry && entry.status !== 'none');
    return {
      color,
      weight: isAssignedOrActive ? 5 : 3,
      opacity: isAssignedOrActive ? 0.95 : 0.55,
    };
  }

  _refreshStyle(directionId) {
    const entry = this.directionState.get(directionId);
    if (entry) {
      entry.layer.setStyle(this._styleFor(directionId));
      entry.hitLayer.setTooltipContent(this._tooltipFor(directionId));
    }
  }

  _handleEdgeClick(directionId) {
    if (this.mode === 'admin') {
      this._addToChain(directionId);
    } else {
      const entry = this.directionState.get(directionId);
      if (entry.assignment && this.onVideoOpen) {
        this.onVideoOpen(entry.directionalLine, entry.assignment);
      }
    }
  }

  // -------------------------------------------------------------------
  // Ketten-Bildung (nur Admin-Modus)
  // -------------------------------------------------------------------

  static _endpointsFor(direction, props) {
    return direction === 'hin'
      ? { start: props.von_ril100, end: props.bis_ril100 }
      : { start: props.bis_ril100, end: props.von_ril100 };
  }

  _addToChain(directionId) {
    const entry = this.directionState.get(directionId);
    if (!entry) return;
    const dl = entry.directionalLine;

    if (entry.inChain) {
      // Erneuter Klick auf ein bereits in der Kette enthaltenes Glied:
      // nichts tun (Entfernen erfolgt gezielt ueber removeLastFromChain()).
      return;
    }

    const { start, end } = StreckenMapWidget._endpointsFor(dl.direction, dl.props);
    const link = {
      edge_id: dl.edge_id,
      direction: dl.direction,
      streckennummer: dl.props.streckennummer,
      von_ril100: dl.props.von_ril100,
      bis_ril100: dl.props.bis_ril100,
      start,
      end,
      dlId: directionId,
    };

    if (this.currentChain.length === 0) {
      this.currentChain = [link];
    } else {
      const first = this.currentChain[0];
      const last = this.currentChain[this.currentChain.length - 1];
      if (start === last.end) {
        this.currentChain.push(link);
      } else if (end === first.start) {
        this.currentChain.unshift(link);
      } else {
        // Nicht angrenzend an eines der beiden Kettenenden: neue Kette
        // beginnen (alte Auswahl wird verworfen).
        this._clearChainStyles();
        this.currentChain = [link];
      }
    }

    entry.inChain = true;
    this._refreshStyle(directionId);
    this._notifyChainChange();
  }

  /**
   * Entfernt das zuletzt hinzugefuegte Kettenglied (Button "letzte Kante
   * entfernen" im Admin-Panel).
   */
  removeLastFromChain() {
    const link = this.currentChain.pop();
    if (link) {
      const entry = this.directionState.get(link.dlId);
      if (entry) {
        entry.inChain = false;
        this._refreshStyle(link.dlId);
      }
    }
    this._notifyChainChange();
  }

  /** Verwirft die aktuelle Kette komplett (Button "Auswahl verwerfen"). */
  clearChain() {
    this._clearChainStyles();
    this.currentChain = [];
    this._notifyChainChange();
  }

  _clearChainStyles() {
    this.currentChain.forEach((link) => {
      const entry = this.directionState.get(link.dlId);
      if (entry) {
        entry.inChain = false;
        this._refreshStyle(link.dlId);
      }
    });
  }

  _notifyChainChange() {
    if (this.onChainChange) {
      this.onChainChange(this.currentChain.slice());
    }
  }

  /**
   * Markiert eine (bereits gespeicherte) Kette von Kanten als "diesem
   * Abschnitt zugeordnet" - Admin-Ansicht beim initialen Laden.
   * @param {Array} edgeRefs - [{edge_id, direction}, ...]
   * @param {object} assignment
   */
  setChainAssignment(edgeRefs, assignment) {
    edgeRefs.forEach((ref) => {
      const directionId = `${ref.edge_id}__${ref.direction}`;
      this.setAssignment(directionId, assignment);
    });
  }

  /**
   * Admin: Video-Zuordnung fuer eine Richtungslinie speichern.
   */
  setAssignment(directionId, assignment) {
    const entry = this.directionState.get(directionId);
    if (!entry) return;
    entry.assignment = assignment;
    this._refreshStyle(directionId);
  }

  /**
   * User: Fortschritt fuer eine Richtungslinie setzen und Farbe aktualisieren.
   * Wird bei einem mehrkantigen Abschnitt fuer JEDE beteiligte Kante mit
   * identischen Werten aufgerufen (siehe Implementierungsplan) - die ganze
   * Kette erscheint dadurch einheitlich eingefaerbt.
   * @param {string} directionId
   * @param {object} assignment - { videoUrl, requiredViews, title, sectionid, assignmentid }
   * @param {number} views - bisherige Anzahl angesehener Views
   */
  setUserProgress(directionId, assignment, views) {
    const entry = this.directionState.get(directionId);
    if (!entry) return;
    entry.assignment = assignment;
    entry.views = views;
    if (!assignment) {
      entry.status = 'none';
    } else if (views <= 0) {
      entry.status = 'offen';
    } else if (views < assignment.requiredViews) {
      entry.status = 'in_bearbeitung';
    } else {
      entry.status = 'erledigt';
    }
    this._refreshStyle(directionId);
  }

  getDirectionEntry(directionId) {
    return this.directionState.get(directionId);
  }

  getCurrentChain() {
    return this.currentChain.slice();
  }

  // -------------------------------------------------------------------
  // Knoten (einzelne Betriebsstellen, "Ortskenntnis")
  // -------------------------------------------------------------------

  /**
   * Zeichnet fuer jede Betriebsstelle einen anklickbaren Kreis-Marker.
   * @param {object} nodesGeoJSON
   */
  loadNodes(nodesGeoJSON) {
    this.nodeState.clear();
    this.nodeLayerGroup.clearLayers();

    nodesGeoJSON.features.forEach((feature) => {
      const [lon, lat] = feature.geometry.coordinates;
      const ril100 = feature.properties.ril100;
      const layer = L.circleMarker([lat, lon], this._nodeStyleFor(ril100));
      layer.on('click', (e) => {
        L.DomEvent.stopPropagation(e);
        layer.openTooltip(e.latlng);
        this._handleNodeClick(ril100);
      });
      // Erst den State-Eintrag setzen, dann den Tooltip bauen - _nodeTooltipFor()
      // liest den State über nodeState.get(ril100) und würde sonst auf
      // "entry undefined" laufen (siehe loadEdges() oben: dort ist die
      // Reihenfolge deswegen bewusst richtig herum).
      this.nodeState.set(ril100, {
        feature,
        layer,
        assignment: null,
        status: 'none',
        views: 0,
        selected: false,
      });
      layer.bindTooltip(this._nodeTooltipFor(ril100), { sticky: true });
      this.nodeLayerGroup.addLayer(layer);
    });
  }

  _nodeStyleFor(ril100) {
    const entry = this.nodeState.get(ril100);
    let color;
    if (this.mode === 'admin') {
      color = entry && entry.selected
        ? ADMIN_COLORS.selected
        : (entry && entry.assignment ? ADMIN_COLORS.assigned : ADMIN_COLORS.unassigned);
    } else {
      color = STATUS_COLORS[entry ? entry.status : 'none'];
    }
    const active = this.mode === 'admin'
      ? (entry && (entry.assignment || entry.selected))
      : (entry && entry.status !== 'none');
    return {
      radius: active ? NODE_MARKER_RADIUS_ACTIVE : NODE_MARKER_RADIUS,
      color: '#333333',
      weight: 1,
      fillColor: color,
      fillOpacity: 0.9,
    };
  }

  _nodeTooltipFor(ril100) {
    const entry = this.nodeState.get(ril100);
    const p = entry.feature.properties;
    let html = `${p.name} (${p.ril100})<br>${this.labels.ortskenntnis}`;
    if (this.mode === 'user' && entry.assignment) {
      html += '<br>' + this._progressText(entry);
    }
    return html;
  }

  _refreshNodeStyle(ril100) {
    const entry = this.nodeState.get(ril100);
    if (entry) {
      entry.layer.setStyle(this._nodeStyleFor(ril100));
      entry.layer.setTooltipContent(this._nodeTooltipFor(ril100));
    }
  }

  _handleNodeClick(ril100) {
    const entry = this.nodeState.get(ril100);
    if (!entry) return;
    if (this.mode === 'admin') {
      // Knoten-Auswahl ist unabhaengig von der Kanten-Kette (Entscheidung:
      // Videos auch fuer einzelne Betriebsstellen ohne Fahrtbezug moeglich).
      if (this.selectedNodeRil100 && this.selectedNodeRil100 !== ril100) {
        const prev = this.nodeState.get(this.selectedNodeRil100);
        if (prev) { prev.selected = false; this._refreshNodeStyle(this.selectedNodeRil100); }
      }
      entry.selected = true;
      this.selectedNodeRil100 = ril100;
      this._refreshNodeStyle(ril100);
      if (this.onNodeSelect) this.onNodeSelect(entry.feature);
    } else if (entry.assignment && this.onNodeVideoOpen) {
      this.onNodeVideoOpen(entry.feature, entry.assignment);
    }
  }

  setNodeAssignment(ril100, assignment) {
    const entry = this.nodeState.get(ril100);
    if (!entry) return;
    entry.assignment = assignment;
    this._refreshNodeStyle(ril100);
  }

  setNodeUserProgress(ril100, assignment, views) {
    const entry = this.nodeState.get(ril100);
    if (!entry) return;
    entry.assignment = assignment;
    entry.views = views;
    if (!assignment) {
      entry.status = 'none';
    } else if (views <= 0) {
      entry.status = 'offen';
    } else if (views < assignment.requiredViews) {
      entry.status = 'in_bearbeitung';
    } else {
      entry.status = 'erledigt';
    }
    this._refreshNodeStyle(ril100);
  }

  getNodeEntry(ril100) {
    return this.nodeState.get(ril100);
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { StreckenMapWidget, STATUS_COLORS, ADMIN_COLORS };
}
