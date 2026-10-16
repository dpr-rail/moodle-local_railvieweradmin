# Lernmethoden – Einrichtung und Bedienung

Das Feature wird unter *Website-Administration → Plugins → Lokale Plugins → Railviewer Admin → Lernmethoden*
mit **„Lernmethoden aktivieren“** eingeschaltet. Ausgeschaltet gilt unverändert nur das Ansehen des Streckenvideos.

## Einrichtung (Rolle mit `editsections`)
1. **Lernmethoden** (Kachel) – Selbstfahren, Mitfahren, je bei Nacht; weitere können ergänzt werden.
2. **Berufserfahrungstypen** (Kachel) – z.B. Berufseinsteiger, Quereinsteiger, Erfahren.
3. **Strecken → Anforderungen**: je Strecke die Anforderungsmatrix (Video + Methoden × Typ), die Soll-Dauer
   je Abschnitt und Methode sowie Ersetzungsregeln (z.B. 1× Nacht = 2× Tag; „Automatisch anwenden“ oder Entscheidung
   durch den Lernenden).
4. Optional in den Einstellungen: **Abschlusserklärung** (Text mit Platzhaltern `{firstname}`, `{lastname}`,
   `{fullname}`, `{idnumber}`, `{routes}`, `{einsatzstellen}`, `{duration}`, `{date}`) und **Compliance-Mail**.

## Zuweisen (Rolle mit `assignroutes`)
Nutzer, Strecken/Einsatzstelle und **Berufserfahrungstyp** wählen. Im zweiten Schritt können Strecken mit
Begründung abgewählt werden („erlassen“). Wird eine Strecke ein zweites Mal zugewiesen (z.B. über eine zweite
Einsatzstelle), werden die Anforderungen **kombiniert** (Maximum je Methode, nie addiert).

## Lernende
Unter *Meine Lernfahrten* (`/mod/railviewer/rides.php`): Fahrt erfassen (Datum, Zugnummer, Gesamtstrecke oder
Teilabschnitt von–bis, Ausbilder per Tippsuche). Der Ausbilder erhält eine Moodle-Nachricht und bestätigt oder lehnt ab.
Bei einem Ausbilder eines anderen EVU: Name, EVU und Unterschrift – die Fahrt zählt sofort. Eine Matrix zeigt je
Abschnitt und Methode, was noch fehlt; die Gesamtdauer des Auftrags wird angezeigt. Ist die Abschlusserklärung aktiv,
gilt der Auftrag erst nach deren Bestätigung als erledigt.

## Berichte (Rolle mit `viewprogress`)
* **Compliance-Matrix** (Soll): Strecken × Einsatzstellen × Methoden, pro Berufserfahrungstyp, als PDF.
* **Compliance-Bericht Soll/Ist** je Mitarbeiter als Ansicht und PDF; wird nach vollständigem Abschluss automatisch gemailt.

## CSV-Import von Abschnitten
*Strecken → Abschnitte → CSV-Import.* Spalten `name`, `description`, `routename`, `video_source` (`vimeo|ignite|sharepoint|s3`),
`video_ref`. Es gibt eine Vorschau mit Zeilenprüfung und eine Beispieldatei.

## Webservice
Zusätzlich zu den bisherigen Funktionen: `local_railvieweradmin_get_methods`, `local_railvieweradmin_get_experience_types`;
`assign_routes` akzeptiert `experiencetypeid`, `waive`, `waivereason`; `get_user_progress` liefert `missing[]`,
`declaration`, `ordercomplete` und die Dauer (Minuten).

## Daten-Import per CSV/ZIP (`import_data.php`)
Kachel „Daten-Import (CSV/ZIP)“ (Recht `editsections`). Elf Dateitypen in dieser Reihenfolge: `methoden`, `erfahrungstypen`,
`abschnitte`, `strecken`, `strecken_abschnitte`, `abschnitt_dauer`, `anforderungen` (Anzahl Lernfahrten je Strecke,
Berufserfahrungstyp und Methode – eine Zeile je Strecke × Typ, eine Spalte je Methode plus `video`), `ersetzungen`,
`einsatzstellen`, `einsatzstellen_strecken`, `zuweisungen`.

* **Nur neu anlegen:** Vorhandenes wird übersprungen, nie überschrieben.
* **Verweise** über `key` (optional, steht an Abschnitten/Strecken/Einsatzstellen) oder den Namen; Methoden und Typen über den Namen.
* **Vorschau** immer zuerst (derselbe Lauf in einer Transaktion, die zurückgerollt wird – nötig: InnoDB/PostgreSQL).
* **Exporte im Importformat:** „Leere Vorlage“, „Fehlendes ergänzen“ (alle vorhandenen Verweise, leere Zellen zum Ausfüllen,
  z. B. Strecke × Typ ohne Anzahl, Abschnitte ohne Dauer, Nutzer ohne Zuweisung) und „Bestand“; auch als ZIP aller Dateien.
  Leere auszufüllende Zeilen werden beim Wiederimport übersprungen.
* Nutzer in `zuweisungen` werden über Benutzername oder ID-Nummer gefunden; bei aktivem Feature ist der Berufserfahrungstyp
  erforderlich (oder es gibt nur einen aktiven Typ).
