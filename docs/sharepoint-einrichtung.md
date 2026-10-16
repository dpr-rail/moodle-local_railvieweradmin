# SharePoint als Videoquelle einrichten – Anleitung für Administrator:innen

**Stand:** 2026-09-16 · **Plugin-Version:** `local_railvieweradmin` ab `202609160009`
**Zielgruppe:** Moodle-/Totara-Administrator:innen zusammen mit einer Person, die in Microsoft
Entra ID (Azure AD) des eigenen Microsoft-365-Mandanten mindestens die Rolle
**Cloud-Anwendungsadministrator** oder **Globaler Administrator** hat (für den Freigabe-Schritt
in Abschnitt 3.5 zwingend erforderlich – ohne diese Rolle lässt sich die Einrichtung technisch
vorbereiten, aber nicht abschließen).

---

## 1. Was diese Funktion tut – und was sie bewusst nicht tut

Seit Plugin-Version `202609160009` steht neben Datei-Upload, Vimeo und ignite.video eine vierte
Videoquelle zur Verfügung: **SharePoint**. Admins können beim Anlegen/Bearbeiten eines
Streckenabschnitts (`edit_section.php`) ein Video direkt aus einer SharePoint-Dokument-/
Videobibliothek auswählen, statt es zusätzlich bei Moodle oder einem separaten Videodienst
hochzuladen.

**Technischer Hintergrund, warum die Umsetzung so aussieht wie sie aussieht:** Für "Stream (on
SharePoint)"-Videos gibt es – anders als bei Vimeo – **keine dokumentierte, per JavaScript
steuerbare Player-Schnittstelle** (kein `postMessage`-Protokoll für Wiedergabeposition/Events).
Ein einfaches SharePoint-"Einbetten"-iframe hätte deshalb bedeutet: keine eigene
Fortschrittsleiste, keine Pflicht-Stopps, keine Anti-Vorspul-Sperre, kein Geschwindigkeitsregler –
also der Verlust genau der Funktionen, die diesen Player von einem einfachen Videofenster
unterscheiden.

Stattdessen löst das Plugin serverseitig über die **Microsoft Graph API** die eigentliche
Videodatei auf und liefert sie wie eine hochgeladene Datei über einen ganz normalen
HTML5-`<video>`-Tag aus. Dadurch funktionieren Fortschrittsleiste, Stopps, Anti-Vorspul-Sperre und
Geschwindigkeitsregler **unverändert und ohne jeden SharePoint-spezifischen Sonderfall** im
Player selbst. Der eigentliche Videoinhalt läuft dabei **nie** über den Moodle-Server – die
lernende Person wird beim Abspielen direkt zu einer von Microsoft signierten, zeitlich begrenzten
Adresse weitergeleitet (siehe `sharepoint_stream.php`).

**Voraussetzung dafür ist ein Zugriff des Plugins auf Microsoft Graph als "App selbst"**
(sogenannter *App-only*-Zugriff per OAuth2-Client-Credentials-Flow) – nicht als ein bestimmter,
eingeloggter Nutzer. Das ist unabhängig davon, dass sich die Moodle-Nutzer:innen selbst bereits
per Entra ID/MFA an Moodle anmelden: Diese Anmeldung autorisiert nur die Person gegenüber Moodle,
nicht die Moodle-Anwendung gegenüber Microsoft Graph. Dafür braucht es eine eigene, einmalig
einzurichtende **Azure-AD/Entra-App-Registrierung** – das ist der Hauptteil dieser Anleitung.

---

## 2. Voraussetzungen

- Ein Microsoft-365-Mandant mit SharePoint Online, in dem die Videos bereits in einer
  Dokumentbibliothek (klassisch oder als "Stream (on SharePoint)"-Videobibliothek) liegen.
- Zugriff auf das [Azure-Portal](https://portal.azure.com) bzw. das
  [Microsoft Entra Admin Center](https://entra.microsoft.com) mit einer Rolle, die
  App-Registrierungen anlegen darf (**Anwendungsentwickler** reicht zum Anlegen; für die
  Admin-Zustimmung in Schritt 3.5 wird zusätzlich **Cloud-Anwendungsadministrator** oder
  **Globaler Administrator** benötigt – ggf. zwei verschiedene Personen).
- Administrationszugriff auf `local_railvieweradmin` in Moodle
  (`moodle/site:config`, i.d.R. Website-Administrator:in).

---

## 3. Azure-AD/Entra-App-Registrierung anlegen

### 3.1 Neue App-Registrierung erstellen

1. Im [Microsoft Entra Admin Center](https://entra.microsoft.com) zu
   **Identität → Anwendungen → App-Registrierungen** navigieren (oder im klassischen
   [Azure-Portal](https://portal.azure.com) zu **Microsoft Entra ID → App-Registrierungen**).
2. **Neue Registrierung** klicken.
3. **Name:** z.B. `Railviewer SharePoint-Anbindung` (frei wählbar, erscheint nur intern in der
   Verwaltung).
4. **Unterstützte Kontotypen:** *Nur Konten in diesem Organisationsverzeichnis (nur
   \<Mandantenname\> – Einzelner Mandant)* auswählen – die App wird ausschließlich innerhalb des
   eigenen Mandanten benötigt.
5. **Umleitungs-URI:** leer lassen. Der App-only-Zugriff (Client-Credentials-Flow) ist ein reiner
   Server-zu-Server-Vorgang ohne Nutzer-Anmeldedialog, dafür wird keine Umleitungs-URI benötigt.
6. **Registrieren** klicken.

### 3.2 Tenant-ID und Client-ID notieren

Auf der **Übersichtsseite** der neu angelegten App-Registrierung stehen zwei Werte, die später in
Moodle eingetragen werden:

- **Anwendungs-ID (Client)** → wird in Moodle zu `Anwendungs-ID (Client-ID)`
- **Verzeichnis-ID (Mandant)** → wird in Moodle zu `Tenant-ID (Verzeichnis-ID)`

Beide am besten direkt in einen Passwort-Manager/eine sichere Notiz kopieren.

### 3.3 Client-Secret erstellen

1. Im Menü der App-Registrierung zu **Zertifikate & Geheimnisse** wechseln.
2. Reiter **Client-Geheimnisse** → **Neuer geheimer Clientschlüssel**.
3. Eine Beschreibung vergeben (z.B. `Railviewer Plugin`) und eine **Ablaufzeit** wählen (Microsoft
   erlaubt maximal 24 Monate).
4. **Hinzufügen** klicken.
5. **Sofort den Wert (Spalte "Wert", nicht "Geheime Schlüssel-ID") kopieren** – er wird nur genau
   einmal angezeigt und ist danach nicht mehr abrufbar. Bei Verlust muss ein neues Secret erstellt
   werden.

> ⚠️ **Wichtig – Erinnerung einrichten:** Ein Client-Secret läuft nach der gewählten Zeit
> unwiderruflich ab. Läuft es ab, ohne dass rechtzeitig ein neues erstellt und in Moodle
> eingetragen wurde, funktioniert die SharePoint-Videoquelle nicht mehr (der
> "Verbindung testen"-Button in Moodle zeigt dann den Fehler *"Anmeldung bei Microsoft Entra
> fehlgeschlagen"*). Am besten direkt jetzt einen Kalendereintrag ca. 2 Wochen vor Ablaufdatum
> anlegen.

### 3.4 API-Berechtigung hinzufügen

1. Im Menü der App-Registrierung zu **API-Berechtigungen** wechseln.
2. **Berechtigung hinzufügen** → **Microsoft Graph** → **Anwendungsberechtigungen** (**nicht**
   "Delegierte Berechtigungen" – siehe Kasten unten, warum).
3. Nach `Sites.Read.All` suchen, ankreuzen, **Berechtigungen hinzufügen**.

> **Warum "Anwendungsberechtigungen" (App-only) statt "Delegierte Berechtigungen"?**
> Delegierte Berechtigungen wirken immer nur im Kontext einer konkret angemeldeten Person und
> erfordern einen interaktiven Anmeldedialog mit Access-/Refresh-Token-Verwaltung pro Nutzer:in.
> Das ist für einen Hintergrunddienst wie diesen (Moodle ruft die Videoliste/Downloadadresse
> serverseitig ab, unabhängig davon, wer gerade eingeloggt ist) weder praktikabel noch nötig.
> Anwendungsberechtigungen laufen als "die App selbst" und benötigen dafür einmalig die
> Admin-Zustimmung aus Schritt 3.5 – danach ist kein weiterer Anmeldeschritt mehr nötig.

### 3.5 Admin-Zustimmung erteilen

1. Weiterhin auf der Seite **API-Berechtigungen**: Button
   **Administratorzustimmung für \<Mandantenname\> erteilen** klicken.
2. Im Bestätigungsdialog **Ja** wählen.
3. Der Status der Berechtigung `Sites.Read.All` muss danach in der Spalte "Status" ein grünes
   Häkchen mit "Erteilt für \<Mandantenname\>" zeigen.

Dieser Schritt **erfordert** die Rolle *Cloud-Anwendungsadministrator* oder *Globaler
Administrator* – ohne Admin-Zustimmung bleibt die Berechtigung wirkungslos, selbst wenn sie unter
3.4 hinzugefügt wurde.

### 3.6 (Empfohlen) Zugriff auf `Sites.Selected` statt `Sites.Read.All` einschränken

`Sites.Read.All` gewährt der App **lesenden Zugriff auf alle SharePoint-Sites des gesamten
Mandanten** – für die meisten Fälle unnötig weitreichend, wenn nur eine einzelne Site mit
Schulungsvideos angebunden werden soll. Wer dieses Risiko minimieren möchte:

1. Statt `Sites.Read.All` die Berechtigung `Sites.Selected` beantragen (Schritt 3.4, gleiche
   Stelle) und ebenfalls die Admin-Zustimmung erteilen (Schritt 3.5).
2. Mit `Sites.Selected` gewährt die Berechtigung allein **noch keinen** Zugriff – dieser muss
   zusätzlich explizit auf die gewünschte(n) Site(s) erteilt werden, z.B. per PowerShell
   ([PnP.PowerShell](https://pnp.github.io/powershell/)):
   ```powershell
   Connect-PnPOnline -Url "https://IHRETENANT.sharepoint.com/sites/IHRESITE" -Interactive
   Grant-PnPAzureADAppSitePermission -AppId "<Client-ID aus Schritt 3.2>" `
       -DisplayName "Railviewer SharePoint-Anbindung" `
       -Site "https://IHRETENANT.sharepoint.com/sites/IHRESITE" `
       -Permissions Read
   ```
3. Dieser Schritt ist **optional** – mit `Sites.Read.All` funktioniert die Anbindung genauso, nur
   mit einem größeren Berechtigungsumfang als nötig. Für eine erste Einrichtung/einen Test ist
   `Sites.Read.All` der einfachere Weg; für den Produktivbetrieb wird `Sites.Selected` empfohlen.

---

## 4. SharePoint-Site-URL und Bibliotheksnamen ermitteln

1. Die SharePoint-Site öffnen, in der die Schulungsvideos liegen, und die **volle URL** aus der
   Adresszeile kopieren, z.B. `https://ihrefirma.sharepoint.com/sites/Streckenvideos`.
2. Falls die Videos in einer eigenen Bibliothek liegen (nicht in der Standard-Dokumentbibliothek
   "Freigegebene Dokumente"/"Documents"), deren **Anzeigenamen** notieren (z.B. `Videos`) – dieser
   wird später als "Bibliotheksname" eingetragen. Bleibt das Feld in Moodle leer, verwendet das
   Plugin automatisch die Standard-Dokumentbibliothek der Site.

---

## 5. Zugangsdaten in Moodle eintragen

1. In Moodle zu **Website-Administration → Plugins → Lokale Plugins → Railviewer Streckenverwaltung**
   navigieren (oder direkt `Website-Administration → Plugins → local_railvieweradmin`).
2. Zum Abschnitt **SharePoint API Einstellungen** scrollen und folgende Felder ausfüllen:

   | Moodle-Feld | Wert aus dieser Anleitung |
   |---|---|
   | Tenant-ID (Verzeichnis-ID) | aus Schritt 3.2 |
   | Anwendungs-ID (Client-ID) | aus Schritt 3.2 |
   | Client-Secret | aus Schritt 3.3 (der "Wert", nicht die "Geheime Schlüssel-ID") |
   | SharePoint-Site-URL | aus Schritt 4.1 |
   | Bibliotheksname (optional) | aus Schritt 4.2, sonst leer lassen |

3. **Änderungen speichern** klicken.

### 5.1 Verbindung testen

Auf derselben Einstellungsseite im Abschnitt **API-Verbindungstests** gibt es einen Button
**SharePoint API Einstellungen: Verbindung testen**. Er prüft nacheinander:

1. **Anmeldung** (Token-Erwerb über Tenant-ID/Client-ID/Client-Secret),
2. **Site-Auflösung** (wird die Site-URL gefunden?),
3. **Bibliotheks-Auflösung** (wird die angegebene – oder die Standard- – Bibliothek gefunden?).

Ein Fehlschlag benennt konkret, bei welcher der drei Stufen es hakt:

| Fehlermeldung | Wahrscheinlichste Ursache |
|---|---|
| *Keine Zugangsdaten hinterlegt.* | Eines der vier Pflichtfelder (Tenant-ID/Client-ID/Client-Secret/Site-URL) ist leer. |
| *Anmeldung bei Microsoft Entra fehlgeschlagen.* | Tenant-ID, Client-ID oder Client-Secret falsch eingetragen (Tippfehler, führende/nachgestellte Leerzeichen) oder das Client-Secret ist abgelaufen (siehe Schritt 3.3). |
| *Anmeldung erfolgreich, aber die SharePoint-Site wurde nicht gefunden.* | Site-URL falsch/Tippfehler, oder die Admin-Zustimmung aus Schritt 3.5 wurde nicht erteilt bzw. bei `Sites.Selected` wurde der Site-Zugriff aus Schritt 3.6 nicht gewährt. |
| *Site gefunden, aber die angegebene Bibliothek wurde darin nicht gefunden.* | Bibliotheksname falsch geschrieben (Groß-/Kleinschreibung ist egal, der Name muss aber sonst exakt passen) – zur Probe das Feld leer lassen, um die Standardbibliothek zu verwenden. |

---

## 6. Ein Video auswählen

1. In `local_railvieweradmin` zu **Abschnitte verwalten** navigieren und einen Abschnitt anlegen
   oder bearbeiten (`edit_section.php`).
2. Bei **Video-Quelle** den Eintrag **SharePoint Video auswählen** wählen.
3. Es erscheint eine Such-/Auswahlliste (analog zu Vimeo/ignite.video), die den Inhalt der
   konfigurierten Bibliothek zeigt. Über das Suchfeld kann nach Dateiname gefiltert werden.
4. Gewünschtes Video anklicken → **Auswählen**.
5. Formular speichern.

Beim erneuten Öffnen des Abschnitts erscheint oberhalb des Formulars eine Video-Vorschau
(nativer `<video>`-Player), damit sofort erkennbar ist, welches Video hinterlegt ist.

**Hinweis:** Anders als bei Vimeo/ignite.video bietet SharePoint aktuell **keinen** Direkt-Upload
aus dem Moodle-Formular heraus an (kein Umschalter "Hochladen" neben "Suchen") – das Video muss
vorher bereits in der SharePoint-Bibliothek liegen. Grund: Ein Datei-Upload über die
Microsoft-Graph-API erfordert für größere Videodateien eine mehrteilige
("resumable") Upload-Sitzung, die (anders als bei Vimeo/ignite.video, die dafür jeweils eine
einzelne vorsignierte URL anbieten) deutlich aufwändiger umzusetzen ist und über diese Anleitung
hinausgeht.

---

## 7. Wer darf ein SharePoint-Video sehen?

Die Zugriffsprüfung ändert sich durch SharePoint **nicht**: Wie bei hochgeladenen Videos prüft
`sharepoint_stream.php` bei jedem Aufruf serverseitig über
`local_railvieweradmin_user_can_access_section()`, ob die anfragende Person entweder
Railviewer-Admin ist oder eine Zuweisung besitzt, die genau diesen Abschnitt enthält. Erst danach
wird die signierte Microsoft-Adresse aufgelöst und der Browser dorthin weitergeleitet. Wer in
SharePoint selbst eventuell zusätzlich (z.B. per Freigabe-Link) Zugriff auf die Rohdatei hätte,
spielt für den Zugriff **über Moodle** keine Rolle – umgekehrt muss die Datei in SharePoint aber
so freigegeben sein, dass die App (nicht die einzelne Person!) sie über die erteilte
Graph-Berechtigung lesen kann (bei `Sites.Read.All`/`Sites.Selected` mit Zugriff auf die gesamte
Site ist das automatisch der Fall).

---

## 8. Grenzen dieser Integration (bewusste Entscheidungen)

- **Kein Direkt-Upload aus Moodle** (siehe Abschnitt 6) – Videos müssen vorher in SharePoint
  liegen.
- **Kein Live-Statusabgleich wie bei ignite.video** (dort wird ein noch kodierendes Video mit
  Fortschrittsbalken angezeigt) – ein Graph-`driveItem` mit `video`-Facette gilt als sofort
  abspielbar, es gibt keinen vergleichbaren "wird noch verarbeitet"-Zustand in der SharePoint-API.
- **Die signierte Download-Adresse ist zeitlich begrenzt gültig.** Jeder Seitenaufruf, der ein
  SharePoint-Video neu lädt (z.B. Wechsel zum nächsten Abschnitt in `play.php`), fordert über
  `sharepoint_stream.php` automatisch eine frische Adresse an – das erfordert keine
  Handlung durch Admin oder Lernende, wird hier nur der Vollständigkeit halber erwähnt.
- **Datenschutz-Auswirkungen** sind im jeweiligen `docs/DATENSCHUTZ.md` (Abschnitt 5 bzw. 4)
  beider Plugins dokumentiert.
