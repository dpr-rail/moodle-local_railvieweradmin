# Datenschutz-Guide – local_railvieweradmin

**Stand:** 2026-09-15 · **Plugin-Version:** siehe `version.php` (`202609130007`, Release v1.1.0)
**Zielgruppe:** Moodle-/Totara-Administrator:innen, Datenschutzbeauftragte, Entwickler:innen

> Dieses Dokument ersetzt keine Rechtsberatung. Es beschreibt technisch, welche
> personenbezogenen Daten das Plugin verarbeitet, welche externen Dienste/Bibliotheken
> eingebunden sind und welche Risiken/Abhilfemaßnahmen sich daraus ergeben. Die rechtliche
> Bewertung (Rechtsgrundlage, Aufbewahrungsfristen, Betroffenenrechte-Prozess, AVV-Abschluss)
> obliegt dem Betreiber.

Siehe auch das Gegenstück [`railviewer/docs/DATENSCHUTZ.md`](../../railviewer/docs/DATENSCHUTZ.md)
für die nutzerseitige (Kursaktivität) Perspektive – beide Plugins teilen sich denselben
Datenbestand (`local_railvieweradmin` verwaltet ihn, `mod_railviewer` liest/schreibt ihn beim
Abspielen).

---

## 1. Zweck des Plugins

`local_railvieweradmin` ist die Verwaltungsoberfläche für Streckenvideos, Streckenkarten,
Overlays, EBuLa-Fahrpläne, Strecken-Zuweisungen und Fortschrittsauswertung im Rahmen der
Zugführer-Schulung. Es speichert keine eigenen "Kursaktivitäts"-Daten (das übernimmt
`mod_railviewer`), sondern verwaltet die zugrundeliegenden Inhalte und Zuweisungen zentral in
System-Kontext (`CONTEXT_SYSTEM`).

## 2. Verarbeitete personenbezogene Daten

| Tabelle / Feld | Datenart | Betroffene | Zweck |
|---|---|---|---|
| `railviewer_assignments.userid` | Nutzer-ID (FK auf `mdl_user`) | Auszubildende/Triebfahrzeugführer:innen | Zuweisung einer Strecke zu einer Person, Soll-/Ist-Wiederholungen |
| `railviewer_assignments.required_views`, `.completed_views`, `.lastviewed` | Schulungsfortschritt, Zeitstempel | wie oben | Nachweis des Schulungsstands (arbeitsrechtlich/betrieblich relevant) |
| `railviewer_views.userid`, `.assignmentid`, `.routeid`, `.sectionid`, `.position`, `.timemodified` | granulares Wiedergabe-Tracking (welche Videoposition wurde wann erreicht) | wie oben | Fortschrittsberechnung, Wiederaufnahme an letzter Position |
| `railviewer_einsatzstellen`/`railviewer_einsatz_routes` | keine direkten Personendaten (Gruppierung von Strecken) | – | organisatorische Bündelung für Massenzuweisung |
| `config_plugins` (Moodle-Kern, durch dieses Plugin befüllt) | API-Zugangsdaten (Vimeo-Token, ignite.video-Key, SharePoint-App-Secret, EBuLa-Zugangsdaten) | kein Nutzer-Personenbezug, aber sensibles Betriebsgeheimnis | Anbindung externer Dienste |
| `railviewer_ebulas.xml`, `railviewer_ebula_section.{html,json}` | Rohdaten importierter EBuLa-Fahrpläne | ggf. Zugnummern, Streckendaten; **kein** direkter Personenbezug im Plugin-Code, abhängig vom Inhalt der Quelldaten des jeweiligen EBuLa-Anbieters | Zuordnung Video↔Fahrplan-Position |
| `railviewer_overlays.name` u.ä. Freitextfelder, `railviewer_sections.description`/`.name` | von Admins frei eingegebener Text | potenziell, falls Admin dort Namen/Orte einträgt | Beschriftung im Player |
| Video-Dateien selbst (`videofileitemid` bei `video_source=upload`, sonst extern bei Vimeo/ignite/SharePoint) | Bildmaterial aus dem Führerstand | **Dritte** (Personen auf Bahnsteigen, Bahnübergängen, im Gleisbereich – keine Moodle-Nutzer) | Schulungsinhalt |

**Wichtig zu Video-Dateien:** Ob Aufnahmen erkennbare Personen zeigen und ob dafür eine
Rechtsgrundlage/Anonymisierung nötig ist, ist eine organisatorische Frage des Betreibers
(Aufnahmeprozess), keine Frage des Plugin-Codes – wird hier nur der Vollständigkeit halber
aufgeführt, da die Dateien über dieses Plugin verwaltet/veröffentlicht werden.

## 3. Speicherort & Aufbewahrung

- **Datenbank:** alle Tabellen liegen in der Moodle-Datenbank des Betreibers (kein externer
  Datenbankdienst).
- **Video-Dateien bei `video_source=upload`:** Moodles eigener Dateispeicher (`moodledata`) auf
  dem Moodle-Server.
- **Video-Dateien bei `video_source=vimeo`/`ignite`:** liegen **ausschließlich** beim jeweiligen
  Anbieter (Vimeo bzw. ignite.video/CDN `railsky.euvideocdn.com`), das Plugin speichert nur die
  externe Video-ID.
- **Video-Dateien bei `video_source=sharepoint`:** liegen ausschließlich in der vom Betreiber
  selbst gehosteten SharePoint-/Microsoft-365-Umgebung (bleibt also, anders als Vimeo/ignite,
  typischerweise **innerhalb** der eigenen Organisation). Das Plugin speichert nur die
  Microsoft-Graph-IDs (`sharepoint_drive_id`/`sharepoint_item_id`), keine Kopie der Datei. Beim
  Abspielen wird der Browser der Lernperson direkt zu einer von Microsoft signierten,
  zeitlich begrenzten Adresse umgeleitet (siehe `sharepoint_stream.php`) - der eigentliche
  Videoinhalt läuft nie über den Moodle-Server, aber der Browser der Lernperson kontaktiert
  dabei Microsoft-Infrastruktur direkt (siehe Abschnitt 5).
- **Video-Dateien bei `video_source=s3`:** liegen in einem vom Betreiber gewählten
  S3-kompatiblen Object Storage (Standort je nach Anbieter/Region, z. B. EU bei Hetzner). Das
  Plugin speichert nur den Objekt-Key (`s3_object_key`), keine Kopie. Der Bucket bleibt privat;
  beim Abspielen und Hochladen kontaktiert der Browser den Storage direkt über signierte,
  zeitlich begrenzte Adressen (siehe `docs/s3-einrichtung.md`).
- **Aufbewahrungsdauer:** Es gibt aktuell **kein automatisches Löschkonzept**. Zuweisungen,
  Fortschrittsdaten und EBuLa-Importe bleiben unbegrenzt bestehen, bis ein Admin sie manuell
  löscht (z.B. über `assignments.php`/`ebulas.php`). Für Art. 5 Abs. 1 lit. e DSGVO
  (Speicherbegrenzung) sollte der Betreiber ein organisatorisches Löschkonzept (z.B. X Jahre
  nach Vertragsende) definieren und manuell durchsetzen (siehe Abschnitt 7).

## 4. Zugriffsberechtigungen

Definiert in `db/access.php`:

| Capability | Kontext | Bedeutung |
|---|---|---|
| `local/railvieweradmin:managesettings` | `CONTEXT_SYSTEM`, kein Archetyp (nur Website-Administratoren) | Plugin-Einstellungen: API-/S3-Zugangsdaten, Webhook. |
| `local/railvieweradmin:editsections` | `CONTEXT_SYSTEM`, `manager` | Strecken/Abschnitte, Overlays, Stopps anlegen/bearbeiten, EBuLa-Fahrpläne abrufen/bearbeiten. |
| `local/railvieweradmin:deletesections` | `CONTEXT_SYSTEM`, `manager` | Strecken/Abschnitte, Overlays, Stopps, EBuLa-Daten löschen. |
| `local/railvieweradmin:manageeinsatzstellen` | `CONTEXT_SYSTEM`, `manager` | Einsatzstellen verwalten. |
| `local/railvieweradmin:assignroutes` | `CONTEXT_SYSTEM`, `manager` | Strecken Nutzern zuweisen (Weboberfläche und Webservice); sieht dabei den Fortschritt **aller** Nutzer (`assignments.php`). |
| `local/railvieweradmin:viewprogress` | `CONTEXT_SYSTEM`, `manager` | Bearbeitungsstand von Nutzern über den Webservice abrufen. |

Das frühere Sammelrecht `local/railvieweradmin:manage` entfällt. Fortschrittsdaten **aller**
zugewiesenen Nutzer sind für `assignroutes` (Weboberfläche) und `viewprogress` (Webservice)
einsehbar – das ist so gewollt (Schulungsaufsicht), sollte aber bei der Rollenvergabe
berücksichtigt werden.

### Webservice und Webhook

Der Webservice „Railviewer API“ (`db/services.php`, Kurzname `railviewer_api`, auf berechtigte
Nutzer beschränkt) bietet `local_railvieweradmin_get_routes`, `local_railvieweradmin_assign_routes`
und `local_railvieweradmin_get_user_progress`. Optional meldet ein Webhook (Einstellungen) die Events
`route_completed` und `all_routes_completed` mit Nutzer-ID, Benutzername und ID-Nummer an eine
konfigurierte URL – damit verlassen personenbezogene Daten das System; Ziel und Rechtsgrundlage
sind vom Betreiber festzulegen.

## 5. Fremdbibliotheken & externe Dienste – Risikoregister

| # | Bibliothek / Dienst | Einbindungsart | Übertragene Daten | Anbieter / Sitz | Risiko | Abhilfemaßnahmen |
|---|---|---|---|---|---|---|
| 1 | **Leaflet.js 1.9.4** (`thirdpartylibs/leaflet/`) | Lokal gebündelt (self-hosted), keine Laufzeit-Fremdkontakte durch die Bibliothek selbst | – | Leaflet-Projekt (BSD-2-Clause, Open Source) | **Niedrig** | Version im Blick behalten (aktuell 1.9.4), bei Sicherheitslücken (selten, z.B. XSS über ungeprüften Popup-HTML-Inhalt) zeitnah aktualisieren. Selbst gehostet = kein IP-Leak an ein CDN. |
| 2 | **Vimeo** (`api.vimeo.com`, `player.vimeo.com`) | API-Aufrufe vom Server (Video-Verwaltung/Upload) + Player-iframe/-Skript im Browser der Admin-Vorschau (`ebula_section_map.php`, `edit_overlay.php`) | Server→Vimeo: API-Zugangsdaten, Video-Metadaten (Titel/Beschreibung), Videodatei-Bytes beim Upload. Browser→Vimeo: IP-Adresse, Geräte-/Browserdaten, ggf. Vimeo-Cookies beim Laden des Players | Vimeo, LLC (USA) | **Mittel–hoch** (US-Anbieter, Drittlandtransfer, Cookie-/Tracking-Möglichkeit beim eingebetteten Player) | Auftragsverarbeitungsvertrag (DPA) mit Vimeo abschließen; in Vimeo-Account "Datenschutzerweiterte Einbettung"/Do-Not-Track-Optionen aktivieren, falls verfügbar; Videos auf "unlisted"/"privat" setzen (im Upload-Formular bereits vorgesehen); Datenschutzerklärung der Moodle-Instanz um Vimeo-Hinweis ergänzen; API-Token mit minimalen Scopes ausstatten (kein "upload"-Scope, falls Upload-Funktion nicht genutzt wird). |
| 3 | **ignite.video** (`app.ignitevideo.cloud`, `play.ignite.video`, CDN `railsky.euvideocdn.com`) | Analog zu Vimeo: Server-API-Aufrufe (Verwaltung/Upload) + Player-iframe im Browser | Server→ignite: API-Key, Video-Metadaten, Videodatei-Bytes beim Upload. Browser→ignite/CDN: IP-Adresse, Geräte-/Browserdaten beim Laden des eingebetteten Players | ignite.video (Anbieter-Sitz vom Betreiber zu klären – nicht öffentlich dokumentiert) | **Mittel** (Anbieter/Serverstandort unklar, daher Drittlandtransfer nicht ausschließbar) | AVV mit ignite.video prüfen/abschließen; Serverstandort erfragen; API-Key-Berechtigungen minimal halten (nur `videos.create`, ggf. ohne `videos.publish`, siehe Hinweistext im Overlay-Formular); embed-URL-Template in den Plugin-Settings ggf. auf eine eigene/datenschutzfreundlichere Player-Domain umstellen (dafür existiert das Setting `ignite_embed_url_template` bereits). |
| 4 | **player.js (Embedly-Protokoll)** (`thirdpartylibs/playerjs/player.js`) | ~~War live von `https://cdn.embed.ly/player-0.1.0.min.js` nachgeladen (NICHT gebündelt), in `play.php` zusätzlich unconditional bei jedem Aufruf~~ – **behoben (2026-09-15):** Datei einmalig heruntergeladen und wie Leaflet unter `thirdpartylibs/playerjs/` self-gehostet, in allen drei Einbindungsstellen (`ebula_section_map.php`, `edit_overlay.php`, `mod_railviewer/play.php`) auf den eigenen, cache-gebusteten `moodle_url`-Pfad umgestellt; weiterhin korrekt an `video_source=ignite` bzw. `$routeusesignite` gekoppelt | – (kein Laufzeit-Fremdkontakt mehr) | Leaflet-analog: Embedly/`github.com/embedly/player.js` (MIT-Lizenz, Ursprungsprojekt) | **Niedrig** (kein Drittanbieter-Kontakt mehr durch das Laden des Skripts) | Version/Sicherheitslücken im Blick behalten wie bei Leaflet (Abschnitt 5 #1); bei einer zukünftigen Protokoll-Änderung durch ignite.video ggf. auf eine neuere Fassung aktualisieren. |
| 5 | **BKG/GeodatenZentrum WMTS** (`sgx.geodatenzentrum.de`) | Kartenkacheln für die Streckenkarte (`streckenmap.php`), URL/Copyright-Text konfigurierbar in `settings.php` | IP-Adresse bei jeder geladenen Kachel | Bundesamt für Kartographie und Geodäsie (deutsche Bundesbehörde) | **Niedrig–mittel** (inländische Behörde, kein Drittlandtransfer, aber dennoch IP-Preisgabe an einen externen Dienst; eigene Protokollierungspraxis des BKG dem Plugin-Autor nicht bekannt) | Datenschutzhinweis der BKG (bkg.bund.de/Datenschutz) in der eigenen Datenschutzerklärung verlinken; bei Bedarf serverseitigen Tile-Proxy einrichten (verhindert direkten Client→BKG-Kontakt, kostet Bandbreite/Wartung – aktuell nicht implementiert). |
| 6 | **EBuLa-Schnittstelle** (admin-konfigurierte URL, `ebula_api_url` – **kein** fest hinterlegter Dienst, Default leer) | Server-zu-Server-Abruf von Fahrplandaten | Zugangsdaten (Benutzer/Passwort/Key) gehen an den vom Betreiber eingetragenen Endpunkt; empfangene Fahrplandaten werden in `railviewer_ebulas`/`railviewer_ebula_section` gespeichert | vom Betreiber gewählter EBuLa-Anbieter | **Mittel** (abhängig vom konkreten Anbieter – vom Betreiber selbst zu bewerten, da hier kein fester Dienst vorgegeben ist) | AVV mit dem gewählten EBuLa-Anbieter abschließen; prüfen, ob importierte Fahrplandaten Personenbezug enthalten (z.B. Tf-Kürzel) und ggf. vor dem Import bereinigen. |
| 7 | **Gespeicherte API-Zugangsdaten** (Vimeo-Token, ignite-Key, SharePoint-Client-Secret, S3-Access/Secret-Keys, EBuLa-Zugangsdaten) | `admin_setting_configpasswordunmask` in `settings.php` | – | – | **Mittel–hoch** – `configpasswordunmask` maskiert nur die UI-Anzeige, **keine Verschlüsselung at rest**: die Werte liegen im Klartext in `mdl_config_plugins`. Ein Token mit z.B. Vimeo-`upload`-Scope erlaubt bei Diebstahl das Hochladen/Löschen beliebiger Videos im verbundenen Account; ein gestohlenes SharePoint-Client-Secret erlaubt (je nach Berechtigungsumfang der App-Registrierung, siehe Zeile 8) lesenden Zugriff auf die konfigurierte(n) SharePoint-Site(s). | DB-Zugriff eng auf Personen mit Administrationsbedarf beschränken; wo möglich Verschlüsselung der Datenbank at rest aktivieren (serverseitig, außerhalb des Plugins); Tokens/Secrets mit minimal nötigen Scopes ausstatten; Zugangsdaten regelmäßig rotieren (beim SharePoint-Client-Secret durch dessen Ablaufdatum ohnehin erzwungen, siehe `docs/sharepoint-einrichtung.md`). |
| 8 | **SharePoint / Microsoft Graph** (`login.microsoftonline.com`, `graph.microsoft.com`, sowie die vom Graph-`/content`-Endpunkt zurückgegebene, signierte SharePoint-/OneDrive-CDN-Adresse) | Server-zu-Server: App-only-Zugriff (Client-Credentials-Flow) zur Videoauswahl (`ajax/get_sharepoint_videos.php`) und Auflösung der Download-Adresse (`sharepoint_stream.php`). Browser der Lernperson wird anschließend direkt zur signierten Adresse umgeleitet, um die Videodatei zu laden (kein Proxy durch den Moodle-Server) | Server→Microsoft: App-Zugangsdaten (Client-ID/-Secret), Dateiname/-metadaten. Browser→Microsoft: IP-Adresse, Geräte-/Browserdaten beim Laden der Videodatei - **kein** `$USER->id` oder sonstiger Moodle-Personenbezug wird an Microsoft übertragen, die Autorisierungsprüfung (`local_railvieweradmin_user_can_access_section()`) läuft ausschließlich serverseitig vorher | Microsoft (Tenant des Betreibers selbst, siehe `docs/sharepoint-einrichtung.md`) | **Niedrig–mittel** (im Regelfall dieselbe Organisation wie der Moodle-Betreiber, kein zusätzlicher Drittanbieter-Vertrag nötig; Risiko liegt v.a. im Berechtigungsumfang der App-Registrierung, siehe Empfehlung) | App-Registrierung nach Möglichkeit auf `Sites.Selected` statt des tenant-weiten `Sites.Read.All` umstellen und explizit nur auf die benötigte(n) Site(s) berechtigen (siehe "Härtung" in `docs/sharepoint-einrichtung.md`); Client-Secret-Ablauf im Kalender vormerken. |
| 9 | **S3 Object Storage** (Endpoint laut Plugin-Settings, z. B. Hetzner) | Server→Storage: Auflisten (`ajax/get_s3_videos.php`), Verbindungstest; Browser→Storage: signierter `PUT`-Upload (Admin) und `GET`-Wiedergabe (Lernende, `play.php`) | Server→Storage: Zugangsdaten (Signatur), Dateinamen. Browser→Storage: IP-Adresse, Geräte-/Browserdaten, Videodatei. **Kein** `$USER->id`/Moodle-Personenbezug | Storage-Anbieter des Betreibers (Standort je Region) | **Niedrig–mittel** (EU-Standort: kein Drittlandtransfer; Risiko v.a. gestohlene Zugangsdaten bzw. weitergegebene signierte URL) | AVV mit dem Anbieter; Bucket privat lassen; getrennte Upload-Zugangsdaten mit minimalen Rechten nutzen; Signed-URL-Gültigkeit knapp halten; CORS nur für die eigene Moodle-Origin (siehe `docs/s3-einrichtung.md`). |

**Kein** eingebundenes JavaScript-Analytics/Tracking (kein Google Analytics, keine
Google Fonts, kein Facebook-Pixel o.ä.) wurde im Code gefunden.

## 6. Bekannte strukturelle Lücken (Moodle/Totara Privacy API)

- **Kein `classes/privacy/provider.php` implementiert.** Die Moodle-/Totara-Bordmittel für
  Betroffenenrechte (*Website-Administration → Nutzer → Datenschutz und Policys → Datenanfragen*
  bzw. Totara-Äquivalent) erfassen `railviewer_assignments`/`railviewer_views` **aktuell nicht
  automatisch**. Auskunfts- (Art. 15 DSGVO) und Löschanfragen (Art. 17 DSGVO) müssen derzeit
  **manuell per SQL** durch eine DB-administrierende Person bearbeitet werden.
- **Kein Backup/Restore-Support** (`backup/moodle2/...` fehlt für `mod_railviewer`). Betrifft
  primär Datenintegrität (Kurssicherung/-duplizierung nimmt die Strecken-Verknüpfung nicht mit),
  hat aber auch eine Datenschutz-Facette: eine Kurslöschung über den Standard-Kurslöschprozess
  räumt `railviewer_assignments`/`railviewer_views` nicht automatisch mit auf.
- **Empfehlung:** `\core_privacy\local\metadata\provider` sowie
  `\core_privacy\local\request\plugin\provider` (mindestens für `railviewer_assignments` und
  `railviewer_views`) implementieren, damit Lösch- und Auskunftsanfragen über die
  Moodle-/Totara-Bordmittel laufen. Dies ist bewusst **nicht** Teil dieses Dokuments (reine
  Bestandsaufnahme) – kann bei Bedarf als eigene Umsetzung beauftragt werden.

## 7. Handlungsempfehlungen für den Betreiber (Zusammenfassung)

1. Auftragsverarbeitungsverträge mit Vimeo, ignite.video, dem S3-Storage-Anbieter und dem gewählten EBuLa-Anbieter
   abschließen bzw. prüfen (für SharePoint i.d.R. bereits über den bestehenden
   Microsoft-365-Vertrag der eigenen Organisation abgedeckt, siehe Abschnitt 5 #8).
2. ~~`embed.ly player.js` self-hosten statt live von `cdn.embed.ly` zu laden~~ – **erledigt**
   (Abschnitt 5, #4).
3. Datenschutzerklärung der Moodle-/Totara-Instanz um Hinweise zu Vimeo, ignite.video und dem
   BKG-Kartendienst ergänzen.
4. API-Zugangsdaten mit minimal nötigen Scopes versehen und regelmäßig rotieren; DB-Zugriff eng
   beschränken.
5. Löschkonzept für `railviewer_assignments`/`railviewer_views` festlegen (aktuell nur manuell
   umsetzbar, siehe Abschnitt 6).
6. Prüfen, ob Video-Aufnahmen erkennbarer Dritter (Bahnsteige, Bahnübergänge) einer Anonymisierung
   bedürfen (organisatorische Frage, außerhalb des Plugin-Codes).
7. Perspektivisch: `core_privacy`-Provider implementieren lassen, um Betroffenenanfragen zu
   automatisieren.


## Lernmethoden-Feature (optional, `learning_methods_enabled`)

Bei aktiviertem Feature werden zusätzlich folgende personenbezogene Daten verarbeitet:

| Daten | Tabelle | Zweck | Hinweise |
|---|---|---|---|
| Lernfahrten (Datum, Zugnummer, Strecke/Abschnitte, Lernmethode, Ausbilder, Status) | `railviewer_rides`, `railviewer_ride_sections` | Nachweis der Streckenkenntnis | Werden bei Löschung des Nutzers entfernt |
| Name und EVU externer Ausbilder, **Unterschrift als PNG** | `railviewer_rides`, Dateispeicher (`local_railvieweradmin`/`ridesignature`) | Bestätigung ohne Moodle-Konto | Drittdaten; Rechtsgrundlage und Aufbewahrungsfrist vom Betreiber festzulegen. Abruf nur für Fahrenden, Ausbilder, `assignroutes`/`viewprogress` |
| Abschlusserklärung (Antwort, Text-Snapshot, Ablehnungsgrund) | `railviewer_declarations` | Nachweis der ausdrücklichen Erklärung | Snapshot enthält den gerenderten Text inkl. Name und Strecken |
| Erlassene Strecken (Begründung, Bearbeiter, Zeit) | `railviewer_assignments` | Nachvollziehbarkeit von Abwahlen | |
| Herkunft Einsatzstellen, Versandmarker Bericht | `railviewer_user_einsatz`, `railviewer_compliance_mail` | Soll/Ist-Bericht | |

**Compliance-Bericht per E-Mail:** Nach vollständigem Abschluss (bei aktivierter Abschlusserklärung erst
nach deren Bestätigung) wird der Soll/Ist-Bericht inkl. Unterschriften als PDF an die in den Einstellungen
genannten Adressen gesendet und optional an die E-Mail-Adresse des Mitarbeiters. Nur interne Adressen eintragen;
die Mail verlässt Moodle unverschlüsselt, sofern der Mailserver kein TLS erzwingt.

**Ausbilder-Suche:** Jeder angemeldete Nutzer kann über die Tippsuche (ab 2 Zeichen, max. 20 Treffer) die Namen
aktiver Nutzer finden. Das ist fachlich gewollt, sollte aber bei der Risikobewertung berücksichtigt werden.

### Daten-Import/-Export (CSV/ZIP)
Der Import (`import_data.php`) kann Zuweisungen mit Benutzername/ID-Nummer anlegen; Exporte im Importformat können Benutzernamen enthalten
(„Zuweisungen“, auch Liste der Nutzer ohne Zuweisung). Beides erfordert die Rechte `assignroutes` bzw. `editsections`/`manageeinsatzstellen`
je Dateityp. Temporäre Importdateien liegen kurzzeitig im Moodle-Temp-Verzeichnis und werden nach dem Import oder Abbruch gelöscht.
Siehe auch `docs/sicherheit.md`.
