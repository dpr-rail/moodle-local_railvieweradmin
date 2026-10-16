<?php
require_once(__DIR__.'/../../config.php');
require_login();

// Absoluter Pfad, damit die Validierung unabhängig vom aktuellen Arbeitsverzeichnis des
// PHP-Prozesses funktioniert (lädt auch kopfdaten.xsd aus demselben Verzeichnis nach).
$schemaFile = __DIR__ . '/ebulabfpl_v_1_3.xsd';

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$id = optional_param('id', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/railvieweradmin/ebula_section_edit.php', ['id' => $id,'sectionid'=>$sectionid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('select_ebula', 'local_railvieweradmin'));
$PAGE->set_heading(get_string('select_ebula_for_section', 'local_railvieweradmin', $DB->get_record('railviewer_sections',['id'=>$sectionid], '*', MUST_EXIST)->name));

echo $OUTPUT->header();
echo "<p><button id='save-selection'>" . get_string('save_selected_timetable', 'local_railvieweradmin') . "</button></p>";

global $DB;

// stop-Daten vorbereiten
$ebula = $DB->get_record('railviewer_ebulas', ['id' => $id], '*', MUST_EXIST);

$xmlstring = trim($ebula->xml);
if ($xmlstring === '') {
    throw new moodle_exception('xml_string_empty', 'local_railvieweradmin', new moodle_url('/local/railvieweradmin/index_ebulas.php'));
}

libxml_use_internal_errors(true); // Fehler sammeln

$dom = new DOMDocument();
$dom->loadXML($xmlstring); // statt load()

if (!$dom) {
    echo "❌ " . get_string('xml_parse_error', 'local_railvieweradmin') . "<br>";
    foreach (libxml_get_errors() as $error) {
        echo "<pre>" . htmlentities($error->message) . "</pre>";
    }
    libxml_clear_errors();
    exit;
}

if (!$dom->schemaValidate($schemaFile)) {
    echo "❌ " . get_string('xml_not_valid_schema', 'local_railvieweradmin') . "<br>";
    foreach (libxml_get_errors() as $error) {
        echo "<pre>" . htmlentities($error->message) . "</pre>";
    }
    libxml_clear_errors();
} else {
    echo "✅ " . get_string('xml_valid_schema', 'local_railvieweradmin') . "<br>";
}

libxml_use_internal_errors(true);  // Fehler intern sammeln

$xml = simplexml_load_string($xmlstring);

if ($xml === false) {
    echo "❌ " . get_string('xml_simplexml_load_error', 'local_railvieweradmin') . "<br>";
    foreach (libxml_get_errors() as $error) {
        echo htmlspecialchars($error->message) . "<br>";
    }
    libxml_clear_errors();
    exit;
}

$namespaces = $xml->getDocNamespaces(true);
$nsPrefix = array_keys($namespaces)[0];
$nsUri = $namespaces[$nsPrefix];

$xml->registerXPathNamespace('ns', $nsUri);

echo "<!DOCTYPE html><html lang='de'><head><meta charset='UTF-8'><title>" . get_string('bfpl_tables', 'local_railvieweradmin') . "</title>";
echo "<style>
    table {border-collapse: collapse; width: 100%;}
    td, th {border-left: 1px solid #ccc; border-right: 1px solid black; padding: 4px; text-align: left;}
    .simplified {border: 1px solid black; border-collapse: collapse; width: 100%; margin-bottom: 0.5em;}
    .simplified td, .simplified th {border-left: 1px solid black; border-right: 1px solid black; padding: 2px;}
    .simplified td:first-child, .simplified th:first-child {border-left: none;}
    .simplified td:last-child, .simplified th:last-child {border-right: none;}
    .inverse-speed { background-color: black; color: white; }
</style>";
echo "</head><body>";

foreach ($xml->xpath('//ns:BfplTabelle') as $tabelle) {
    $tabelleChildren = $tabelle->children($nsUri);
    $typeEntry = (string)$tabelle['typeEntry'];
    $fahrwegname = (string)$tabelleChildren->Fahrwegname;
    $referencedid = (string)$tabelle['id'] ?? null;

    echo "<h2";
    if ($referencedid) {
        echo ' id="' . htmlspecialchars($referencedid) . '"';
    }
    echo '>' . htmlspecialchars($typeEntry . ' – ' . $fahrwegname) . "</h2>";

    $gruppen = ['links' => [], 'rechts' => []];

    foreach ($tabelleChildren->BfplStrecke as $strecke) {
        $streckeAttrs = $strecke->attributes();
        $streckenName = (string)$streckeAttrs['strecke'];
        $letzterKm = null;
        $letzteEntfernung = null;

        foreach ($strecke->KmGruppe as $kmGruppe) {
            $kmAttrs = $kmGruppe->attributes();
            $km = (string)$kmAttrs['km'];
            $neigung = (string)$kmAttrs['neigung'];
            $radius = (string)$kmAttrs['radius'];
            $gleislageGroup = (string)$kmAttrs['gleislage'];
            $bplZeilen = $kmGruppe->BfplZeile;

            if (count($bplZeilen) > 0) {
                foreach ($bplZeilen as $zeile) {
                    $zeileAttrs = $zeile->attributes();
                    $typeEntryZeile = isset($zeileAttrs['typeEntry']) ? (string)$zeileAttrs['typeEntry'] : '';
                    $langname = '';
                    $latitude = false;
                    $longitude = false;
                    $artFwgpunktZeile='';

                    if ($typeEntryZeile === 'Betriebsstelle') {
                        $langname = $zeile->Langname;
                        if ($zeile->Wgs84koordinate[0] != '') {
                            $latitude = floatval(explode(" ", $zeile->Wgs84koordinate[0])[0]);
                            $longitude = floatval(explode(" ", $zeile->Wgs84koordinate[0])[1]);
                        }
                    }
                    else if ($typeEntryZeile === 'Fahrwegpunkt') {
                       $artFwgpunktZeile = isset($zeileAttrs['art']) ? (string)$zeileAttrs['art'] : '';
                       foreach ($zeile->Mutterbahnhof as $mutterbahnhof){
                            $artFwgpunktZeile=$artFwgpunktZeile." ".$mutterbahnhof;
                       }
                    }

                    $anzahlsaegelinie = ($typeEntryZeile === 'Saegelinie') ? intval($zeileAttrs['anzahl']) : 0;
                    $gleislageZeile = isset($zeileAttrs['gleislage']) ? (string)$zeileAttrs['gleislage'] : '';
                    $inverseZeile = isset($zeileAttrs['istInversDarzustellen']) ? (string)$zeileAttrs['istInversDarzustellen'] : '';
                    $gestrichelteLinieZeile = isset($zeileAttrs['hatGestrichelteLinie']) ? (string)$zeileAttrs['hatGestrichelteLinie'] : '';

                    $kmhZeile = isset($zeileAttrs['kmh']) ? (string)$zeileAttrs['kmh'] : '';
                    $entfernungvonanfangZeile = isset($zeileAttrs['entfernungVomFahrweganfang']) ? (string)$zeileAttrs['entfernungVomFahrweganfang'] : '';
                    $neigungZeile = isset($zeileAttrs['neigung']) ? (string)$zeileAttrs['neigung'] : '';
                    $radiusZeile = isset($zeileAttrs['radius']) ? (string)$zeileAttrs['radius'] : '';
                    $inhalt = trim((string)$zeile);
                    $rowid = (string)$zeileAttrs['AlternativeRef'] ?? null;
                    $id = (string)$zeileAttrs['id'] ?? null;

                    $zuweisungen = [];

                    // Gruppenzuweisung je nach gleislage
                    $gleiswert = $gleislageZeile ?: $gleislageGroup;
                    if (in_array($gleiswert, ['links', 'beidegleise', 'einzig'])) {
                        $zuweisungen[] = 'links';
                    }
                    if (in_array($gleiswert, ['rechts', 'beidegleise', 'einzig'])) {
                        $zuweisungen[] = 'rechts';
                    }

                    $eintrag = [
                        'strecke' => $streckenName,
                        'km' => $km,
                        'entfernung' => $entfernungvonanfangZeile,
                        'typeEntry' => $typeEntryZeile,
                        'neigung' => $neigungZeile ?: $neigung,
                        'radius' => $radiusZeile ?: $radius,
                        'gleislage' => $gleiswert,
                        'geschw' => $kmhZeile,
                        'bstelle' => $langname,
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'info' => $inhalt,
                        'saege' => $anzahlsaegelinie,
                        'id' => $id,
                        'ref' => $rowid,
                        'artfwgpunkt'=>$artFwgpunktZeile,
                        'inverseDarstellung'=>$inverseZeile,
                        'gestrichelteLinie'=>$gestrichelteLinieZeile,
                    ];

                    foreach ($zuweisungen as $seite) {
                        $gruppen[$seite][] = $eintrag;
                    }

                    if ($entfernungvonanfangZeile !== '') {
                        $letzterKm = (float)$km;
                        $letzteEntfernung = (float)$entfernungvonanfangZeile * 1000;
                    }
                }
            } else {
                // Neigung oder Radius ohne BfplZeile
                $entfernungBerechnet = '';
                if ($letzterKm !== null && $letzteEntfernung !== null && $km !== '') {
                    $deltaKm = abs((float)$letzterKm - (float)$km);
                    $entfernungBerechnet = $letzteEntfernung + ($deltaKm * 1000);
                }

                $zuweisungen = [];
                if (in_array($gleislageGroup, ['links', 'beidegleise', 'einzig'])) {
                    $zuweisungen[] = 'links';
                }
                if (in_array($gleislageGroup, ['rechts', 'beidegleise', 'einzig'])) {
                    $zuweisungen[] = 'rechts';
                }

                if ($neigung !== '') {
                    $eintrag = [
                        'strecke' => $streckenName,
                        'km' => $km,
                        'entfernung' => is_numeric($entfernungBerechnet) ? number_format($entfernungBerechnet / 1000, 3, '.', '') : '',
                        'typeEntry' => get_string('gradient', 'local_railvieweradmin'),
                        'neigung' => $neigung,
                        'radius' => '',
                        'gleislage' => $gleislageGroup,
                        'geschw' => '',
                        'bstelle' => '',
                        'lat' => '',
                        'lon' => '',
                        'info' => '',
                        'saege' => '',
                        'id' => '',
                        'ref' => '',
                        'artfwgpunkt'=>'',
                        'inverseDarstellung'=>'',
                        'gestrichelteLinie'=>'',
                    ];
                    foreach ($zuweisungen as $seite) {
                        $gruppen[$seite][] = $eintrag;
                    }
                }

                if ($radius !== '') {
                    $eintrag = [
                        'strecke' => $streckenName,
                        'km' => $km,
                        'entfernung' => is_numeric($entfernungBerechnet) ? number_format($entfernungBerechnet / 1000, 3, '.', '') : '',
                        'typeEntry' => get_string('radius', 'local_railvieweradmin'),
                        'neigung' => '',
                        'radius' => $radius,
                        'gleislage' => $gleislageGroup,
                        'geschw' => '',
                        'bstelle' => '',
                        'lat' => '',
                        'lon' => '',
                        'info' => '',
                        'saege' => '',
                        'id' => '',
                        'ref' => '',
                        'artfwgpunkt'=>'',
                        'inverseDarstellung'=>'',
                        'gestrichelteLinie'=>'',
                    ];
                    foreach ($zuweisungen as $seite) {
                        $gruppen[$seite][] = $eintrag;
                    }
                }
            }
        }
    }

    // Übersichtstabelle oberhalb – reduzierte Darstellung
    foreach (['links', 'rechts'] as $seite) {
        $filterEintraege = [];

        foreach (array_reverse($gruppen[$seite]) as $eintrag) {
            if (in_array($eintrag['typeEntry'], ['Betriebsstelle', 'Geschwindigkeit','Fahrwegpunkt'])) {
                
                $bstelleText = '';

                if ($eintrag['typeEntry'] === 'Fahrwegpunkt' && (string)$eintrag['artfwgpunkt']==='WE') {
                    $bstelleText = get_string('fahrwegpunkt_art_we', 'local_railvieweradmin');
                } elseif ($eintrag['typeEntry'] === 'Fahrwegpunkt') {
                    $bstelleText = isset($eintrag['artfwgpunkt']) ? (string)$eintrag['artfwgpunkt'] : '';
                } elseif ($eintrag['typeEntry'] === 'Betriebsstelle') {
                    $bstelleText = $eintrag['bstelle'];

                    // Betriebsstellennotizen derselben km-Gruppe anhängen
                    $kmMatch = $eintrag['km'];
                    $notizen = array_filter($gruppen[$seite], function($z) use ($kmMatch) {
                        return $z['km'] === $kmMatch && $z['typeEntry'] === 'Betriebsstellennotiz';
                    });

                    foreach ($notizen as $n) {
                        $bstelleText .= ' – ' . $n['info'];
                    }
                }

                $isInverse = ($eintrag['typeEntry'] === 'Geschwindigkeit' && isset($eintrag['inverseDarstellung']) && $eintrag['inverseDarstellung'] == 'true');

                $filterEintraege[] = [
                    'km' => $eintrag['km'],
                    'geschw' => $eintrag['geschw'],
                    'bstelle' => $bstelleText,
                    'isInverse' => $isInverse,
                    'hasSpeed' => $eintrag['geschw'] !== '',
                    'gestrichelteLinie'=>   $eintrag['gestrichelteLinie']                        
                ];
            }
        }

        // Ausgabe der Übersichtstabelle
        echo "<h3>⏫ " . get_string('overview', 'local_railvieweradmin') . " (" . get_string('track_position', 'local_railvieweradmin', ucfirst($seite)) . ")</h3>";
        echo "<table class='simplified'>";
        echo "<tr><th>" . get_string('km', 'local_railvieweradmin') . "</th><th>" . get_string('speed', 'local_railvieweradmin') . "</th><th>" . get_string('operating_point', 'local_railvieweradmin') . "</th></tr>";
        foreach ($filterEintraege as $index=>$row) {
            $speedStyle='';
            if($row['gestrichelteLinie']==='true')
                $speedStyle = " style='border-top: 1px dashed black;'";
            else
                $speedStyle = $row['hasSpeed'] ? " style='border-top: 1px solid black;'" : "";
            
            $speedClass = $row['isInverse'] ? " class='inverse-speed'" : "";

            echo "<tr data-rowindex='$index'>";
            echo "<td>" . htmlspecialchars($row['km']) . "</td>";
            echo "<td$speedClass$speedStyle>" . htmlspecialchars($row['geschw']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bstelle']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }

    // Ausgabe der Tabellen je Gruppe
    foreach (['links', 'rechts'] as $seite) {
        echo "<h3>" . get_string('track_position', 'local_railvieweradmin', ucfirst($seite)) . "</h3>";
        echo "<table>";
        echo "<tr><th>" . get_string('route', 'local_railvieweradmin') . "</th><th>" . get_string('km', 'local_railvieweradmin') . "</th><th>" . get_string('distance', 'local_railvieweradmin') . "</th><th>" . get_string('type_entry', 'local_railvieweradmin') . "</th><th>" . get_string('gradient', 'local_railvieweradmin') . "</th><th>" . get_string('radius', 'local_railvieweradmin') . "</th><th>" . get_string('track_position_short', 'local_railvieweradmin') . "</th><th>" . get_string('speed', 'local_railvieweradmin') . "</th><th>" . get_string('operating_point', 'local_railvieweradmin') . "</th><th>" . get_string('width', 'local_railvieweradmin') . "</th><th>" . get_string('length', 'local_railvieweradmin') . "</th><th>" . get_string('info', 'local_railvieweradmin') . "</th><th>" . get_string('saw_lines', 'local_railvieweradmin') . "</th></tr>";

        foreach ($gruppen[$seite] as $eintrag) {
            echo "<tr" . ($eintrag['id'] ? " id='" . htmlspecialchars($eintrag['id']) . "'" : "") . ">";
            echo "<td>" . htmlspecialchars($eintrag['strecke']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['km']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['entfernung']) . "</td>";

            // Sonderfall: Links bei Abzweigung oder Fortsetzung
            if ($eintrag['typeEntry'] === 'Abzweigung' && $eintrag['ref']) {
                echo "<td><a href='#" . htmlspecialchars($eintrag['ref']) . "'>" . get_string('branch', 'local_railvieweradmin') . "</a></td>";
            } elseif ($eintrag['typeEntry'] === 'Fortsetzung' && $eintrag['ref']) {
                echo "<td><a href='#" . htmlspecialchars($eintrag['ref']) . "'>" . get_string('continuation', 'local_railvieweradmin') . "</a></td>";
            } else {
                echo "<td>" . htmlspecialchars($eintrag['typeEntry']) . "</td>";
            }

            echo "<td>" . htmlspecialchars($eintrag['neigung']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['radius']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['gleislage']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['geschw']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['bstelle']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['lat']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['lon']) . "</td>";
            echo "<td>" . htmlspecialchars($eintrag['info']) . "</td>";
            echo "<td>" . ($eintrag['saege'] !== 0 ? htmlspecialchars($eintrag['saege']) : "") . "</td>";
            echo "</tr>";
        }

        echo "</table><hr>";
    }
}
?>
<style>/* Markierte Zeilen (zwischen Start und Ende) */
tr.selected {
  background-color: #cce5ff;  /* hellblau */
}

/* Start-Zeile */
tr.start {
  background-color: #4a90e2;  /* dunkleres blau */
  color: white;
  font-weight: bold;
}

/* End-Zeile */
tr.end {
  background-color: #d9534f;  /* rot */
  color: white;
  font-weight: bold;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tables = document.querySelectorAll('table.simplified');

  tables.forEach(table => {
    let startIndex = null;
    let endIndex = null;

    function clearSelection() {
      table.querySelectorAll('tr').forEach(row => {
        row.classList.remove('selected', 'start', 'end');
      });
    }

    function highlightSelection(start, end) {
      const [from, to] = start < end ? [start, end] : [end, start];
      for(let i = from; i <= to; i++) {
        const row = table.querySelector(`tr[data-rowindex='${i}']`);
        if(row) row.classList.add('selected');
      }
    }

    table.querySelectorAll('tr[data-rowindex]').forEach(row => {
      row.addEventListener('click', () => {
        const idx = parseInt(row.getAttribute('data-rowindex'));

        if (startIndex === null) {
          startIndex = idx;
          endIndex = null;
          clearSelection();
          row.classList.add('start', 'selected');
        } else if (endIndex === null) {
          endIndex = idx;
          clearSelection();
         
          const startRow = table.querySelector(`tr[data-rowindex='${startIndex}']`);
          const endRow = table.querySelector(`tr[data-rowindex='${endIndex}']`);
          if(startRow && startRow<endRow){
               startRow.classList.add('end');
          }
          else if(startRow && startRow>endRow){
               startRow.classList.add('start');
          }
          if(endRow && startRow<endRow){
               endRow.classList.add('start');
          }
          else if(endRow && startRow>endRow){
               endRow.classList.add('end');
          }
          highlightSelection(startIndex, endIndex);
        } else {
          startIndex = idx;
          endIndex = null;
          clearSelection();
          row.classList.add('start', 'selected');
        }
      });
    });
  });
});

document.getElementById('save-selection').addEventListener('click', function() {
    const table = document.querySelector('.simplified');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tr'));
    const selectedRows = rows.filter(row => row.classList.contains('start') || row.classList.contains('end') || row.classList.contains('selected'));

    const jsonData = selectedRows.map(row => {
        const cells = row.querySelectorAll('td');
        return {
            km: cells[0]?.innerText.trim(),
            geschw: cells[1]?.innerText.trim(),
            bstelle: cells[2]?.innerText.trim(),
            index: row.dataset.rowindex
        };
    });

    const html = selectedRows.map(row => row.outerHTML).join("\n");
    const sectionid = <?= (int)$sectionid ?>;
    const ebulaid = <?= (int)$id ?>;

    fetch('ebula_section_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            sesskey: M.cfg.sesskey,
            sectionid: sectionid,
            ebulaid: ebulaid,
            html: html,
            json: jsonData
        })
    })
    .then(response => response.json())
    .then(data => {
        alert('<?= get_string('js_timetable_saved', 'local_railvieweradmin', '') ?>'.replace('{$a}', data.id));
    })
    .catch(error => {
        alert('<?= get_string('js_save_error', 'local_railvieweradmin', '') ?>'.replace('{$a}', error));
    });
});
</script>

<?php
echo "</body></html>";
?>
