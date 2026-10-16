/**
 * offset-lines.js
 * -----------------------------------------------------------------------
 * Geometrie-Hilfsfunktionen fuer die Kartendarstellung.
 *
 * Zwei Ebenen von Parallelversatz sind noetig:
 *
 *  1) "Spur" (lane): Wenn mehrere Strecken denselben Betriebsstellen-
 *     Abschnitt bedienen (z.B. Fernbahn + S-Bahn zwischen denselben zwei
 *     Punkten), muss jede Strecke als eigener, nebeneinander liegender
 *     Streifen gezeichnet werden - sonst liegen die Linien exakt
 *     uebereinander (identische Endkoordinaten).
 *
 *  2) "Hin/Rueck": Innerhalb der Spur einer einzelnen Strecke bekommt
 *     jede Fahrtrichtung nochmal einen kleineren Versatz.
 *
 * Beide Versaetze sind senkrecht zur Verbindungslinie, in Metern
 * angegeben und werden lokal (equirectangular) in Grad umgerechnet -
 * fuer die kurzen Streckenabschnitte hier voellig ausreichend genau.
 * -----------------------------------------------------------------------
 */

const METERS_PER_DEG_LAT = 111320;
const LANE_SPACING_M = 30;      // Abstand zwischen parallelen Strecken
const DIRECTION_SPACING_M = 9;  // Abstand Hin/Rueck innerhalb einer Spur

function metersToDegLat(m) {
  return m / METERS_PER_DEG_LAT;
}

function metersToDegLon(m, atLat) {
  return m / (METERS_PER_DEG_LAT * Math.cos((atLat * Math.PI) / 180));
}

/**
 * Verschiebt die Linie [[lon1,lat1],[lon2,lat2]] um `offsetMeters`
 * senkrecht zur Verbindungsrichtung. Positiv = "rechts" in Richtung A->B.
 */
function offsetLine(coords, offsetMeters) {
  const [[lon1, lat1], [lon2, lat2]] = coords;

  const midLat = (lat1 + lat2) / 2;
  const dx = (lon2 - lon1) * METERS_PER_DEG_LAT * Math.cos((midLat * Math.PI) / 180);
  const dy = (lat2 - lat1) * METERS_PER_DEG_LAT;
  const len = Math.sqrt(dx * dx + dy * dy) || 1;

  const perpX = dy / len;
  const perpY = -dx / len;

  const offsetXm = perpX * offsetMeters;
  const offsetYm = perpY * offsetMeters;

  function shift(lon, lat) {
    return [lon + metersToDegLon(offsetXm, lat), lat + metersToDegLat(offsetYm)];
  }

  return [shift(lon1, lat1), shift(lon2, lat2)];
}

/**
 * Gruppiert Kanten-Features nach dem (ungeordneten) Betriebsstellen-Paar
 * und weist jeder beteiligten Streckennummer einen "Spur-Versatz" zu, so
 * dass parallele Strecken zwischen denselben zwei Punkten nebeneinander
 * statt uebereinander liegen.
 *
 * @returns Map: edge_id -> laneOffsetMeters
 */
function assignLaneOffsets(edgeFeatures, laneSpacing = LANE_SPACING_M) {
  const groups = new Map(); // stationPairKey -> Set(streckennummer)
  const streckeByEdge = new Map(); // edge_id -> { key, streckennummer }

  edgeFeatures.forEach((f) => {
    const p = f.properties;
    const key = [p.von_ril100, p.bis_ril100].sort().join('|');
    if (!groups.has(key)) groups.set(key, new Set());
    groups.get(key).add(p.streckennummer);
    streckeByEdge.set(p.edge_id, { key, streckennummer: p.streckennummer });
  });

  // Pro Gruppe: Streckennummern deterministisch sortieren, zentriert
  // um 0 verteilen (z.B. 2 Strecken -> -15/+15, 3 Strecken -> -30/0/+30 ...)
  const laneOffsetByGroupAndStrecke = new Map(); // key -> Map(streckennummer -> offset)
  groups.forEach((streckenSet, key) => {
    const streckenList = Array.from(streckenSet).sort();
    const n = streckenList.length;
    const map = new Map();
    streckenList.forEach((nr, i) => {
      const offset = (i - (n - 1) / 2) * laneSpacing;
      map.set(nr, offset);
    });
    laneOffsetByGroupAndStrecke.set(key, map);
  });

  const laneOffsetByEdgeId = new Map();
  streckeByEdge.forEach(({ key, streckennummer }, edgeId) => {
    const offset = laneOffsetByGroupAndStrecke.get(key).get(streckennummer);
    laneOffsetByEdgeId.set(edgeId, offset);
  });

  return laneOffsetByEdgeId;
}

/**
 * Erzeugt aus einem Kanten-Feature die zwei Richtungslinien (hin/rueck),
 * bereits inkl. Spur-Versatz fuer parallele Strecken.
 *
 * @param {object} edgeFeature - GeoJSON LineString-Feature mit edge_id etc.
 * @param {number} laneOffsetMeters - Versatz dieser Strecke innerhalb der
 *   Gruppe gemeinsam bedienter Betriebsstellen (aus assignLaneOffsets)
 * @param {number} directionSpacing - Abstand Hin/Rueck innerhalb der Spur
 */
function buildDirectionalLines(edgeFeature, laneOffsetMeters = 0, directionSpacing = DIRECTION_SPACING_M) {
  const coords = edgeFeature.geometry.coordinates;
  const props = edgeFeature.properties;

  const hinOffset = laneOffsetMeters + directionSpacing / 2;
  const rueckOffset = laneOffsetMeters - directionSpacing / 2;

  const hinCoords = offsetLine(coords, hinOffset);
  const rueckCoordsForward = offsetLine(coords, rueckOffset);
  const rueckCoords = [...rueckCoordsForward].reverse(); // visuell in Gegenrichtung

  return [
    {
      id: `${props.edge_id}__hin`,
      edge_id: props.edge_id,
      direction: 'hin',
      coords: hinCoords,
      laneOffsetMeters,
      props,
    },
    {
      id: `${props.edge_id}__rueck`,
      edge_id: props.edge_id,
      direction: 'rueck',
      coords: rueckCoords,
      laneOffsetMeters,
      props,
    },
  ];
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    offsetLine,
    buildDirectionalLines,
    assignLaneOffsets,
    LANE_SPACING_M,
    DIRECTION_SPACING_M,
  };
}
