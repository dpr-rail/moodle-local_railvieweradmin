# S3 Object Storage als Videoquelle einrichten – Anleitung für Administrator:innen

**Stand:** 2026-10-08 · **Plugin-Version:** `local_railvieweradmin` ab `202610080010`
**Zielgruppe:** Moodle-/Totara-Administrator:innen mit Zugriff auf einen S3-kompatiblen
Object Storage (getestet auf Basis von Hetzner Object Storage, funktioniert prinzipiell mit
jedem S3-kompatiblen Dienst, der Signature V4 unterstützt).

---

## 1. Was diese Funktion tut

Neben Datei-Upload, Vimeo, ignite.video und SharePoint steht **S3 Object Storage** als
Videoquelle zur Verfügung. Beim Anlegen/Bearbeiten eines Streckenabschnitts
(`edit_section.php`) wählt man ein Video aus dem Bucket (mit Ordner-/Präfixfilter) oder lädt ein
neues direkt hoch.

- Der **Bucket bleibt privat.** Zum Abspielen erzeugt `play.php` eine **signierte, zeitlich
  begrenzte URL** (AWS Signature V4). Das Video läuft nie über den Moodle-Server; der Browser
  lädt es direkt vom Storage. Abgespielt wird über den normalen HTML5-`<video>`-Pfad, daher
  funktionieren Fortschrittsleiste, Stopps, Anti-Vorspul-Sperre und Geschwindigkeitsregler
  wie bei hochgeladenen Dateien.
- **Uploads** gehen per signiertem `PUT` direkt vom Browser in den Bucket (Zielpfad
  `railviewer/<Zeitstempel>_<Dateiname>`), ohne PHP-Upload-Limits von Moodle.
- Das Plugin speichert pro Abschnitt nur den **Objekt-Key** (`s3_object_key`), keine Kopie.
- Erlaubte Dateitypen: `mp4`, `m4v`, `webm`, `ogv`, `mov`.
- Es gibt **keine SDK-Abhängigkeit**; die Signierung ist in `classes/s3_client.php`
  implementiert.

## 2. Einstellungen

*Website-Administration → Plugins → Lokale Plugins → Railviewer Admin → Abschnitt
„S3 Object Storage“.* Die Quelle „S3“ erscheint im Abschnittsformular erst, wenn Endpoint,
Bucket, Access Key und Secret Key gesetzt sind.

| Einstellung | Bedeutung |
|---|---|
| Endpoint | z. B. `https://fsn1.your-objectstorage.com` |
| Region | Für die Signierung, z. B. `fsn1`, `nbg1`, `hel1` (Hetzner) |
| Bucket name | Name des Buckets mit den Videos |
| Access key / Secret key | Allgemeine Zugangsdaten: Auflisten, Abspielen und – falls keine eigenen Upload-Zugangsdaten gesetzt sind – auch Upload |
| Access key / Secret key nur für Uploads (optional) | Eigene Zugangsdaten für Browser-Uploads. **Beide** Felder müssen gefüllt sein, sonst gelten die allgemeinen Zugangsdaten |
| Path-style addressing | `endpoint/bucket/key` statt `bucket.endpoint/key`. Empfohlen (Standard an) |
| Signed URL lifetime (hours) | Gültigkeit der Abspiel-URLs, 1–168 h (Standard 12). Sollte länger sein als die längste Sitzung |

Über **„Verbindung testen“** wird Endpoint, Bucket und die *allgemeinen* Zugangsdaten per
Listenabfrage geprüft. Die Upload-Zugangsdaten werden dabei nicht getestet (ein Test würde
eine Datei schreiben); ein Fehler zeigt sich erst beim ersten Upload.

### Getrennte Zugangsdaten für Upload (Empfehlung)

Sinnvoll, um Rechte zu trennen: Die allgemeinen Zugangsdaten brauchen nur **Lesen/Auflisten**,
die Upload-Zugangsdaten nur **Schreiben**. Wird ein Key gestohlen, ist so nicht automatisch
beides möglich. Bei Hetzner Object Storage lassen sich Zugangsdaten pro Projekt anlegen;
feinere Rechte pro Key hängen vom Anbieter ab. Wo das nicht möglich ist, einfach die
optionalen Felder leer lassen.

## 3. CORS-Regel am Bucket (zwingend für Browser-Upload)

Der Browser spricht beim Upload und Abspielen direkt mit dem Bucket, also mit einer anderen
Domain als Moodle. Der Bucket muss das per **CORS** erlauben. Ohne Regel meldet das Formular
„Upload fehlgeschlagen (check bucket CORS rule)“.

`cors.json` (Origin durch die echte Moodle-URL ersetzen, kein `*`):

```json
{
  "CORSRules": [{
    "AllowedOrigins": ["https://moodle.example.de"],
    "AllowedMethods": ["GET", "PUT", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag", "Content-Length", "Content-Range"],
    "MaxAgeSeconds": 3600
  }]
}
```

Setzen, z. B. mit der AWS-CLI:

```
aws s3api put-bucket-cors --bucket MEIN-BUCKET \
  --endpoint-url https://fsn1.your-objectstorage.com \
  --cors-configuration file://cors.json
```

## 4. Einrichtung Schritt für Schritt

1. Bucket anlegen (privat lassen) und Zugangsdaten erzeugen.
2. CORS-Regel setzen (Abschnitt 3).
3. Plugin-Update einspielen und die Moodle-Upgrade-Seite aufrufen (legt das Feld
   `railviewer_sections.s3_object_key` an).
4. Einstellungen ausfüllen (Abschnitt 2) und **„Verbindung testen“** klicken.
5. Abschnitt anlegen/bearbeiten → Videoquelle **S3 Object Storage** → Video wählen oder hochladen
   → speichern.
6. Im Player abspielen und prüfen.

## 5. Fehlersuche

| Symptom | Ursache / Abhilfe |
|---|---|
| Quelle „S3“ fehlt im Formular | Endpoint, Bucket, Access/Secret Key nicht vollständig gesetzt |
| `SignatureDoesNotMatch` | Region oder Path-style falsch, Secret Key falsch, Uhrzeit des Moodle-Servers weicht stark ab |
| `AccessDenied` / `403` | Zugangsdaten ohne Recht auf Auflisten bzw. Schreiben |
| Upload bricht ab | CORS-Regel fehlt oder Origin stimmt nicht exakt (Schema, Host, Port) |
| Video startet nicht nach längerer Zeit | Signierte URL abgelaufen: „Signed URL lifetime“ erhöhen und Seite neu laden |
| Liste leer | Präfixfilter prüfen; nur Dateien mit erlaubter Endung werden gezeigt |

## 6. Sicherheit & Datenschutz

- Zugangsdaten liegen wie alle API-Zugangsdaten **im Klartext** in `mdl_config_plugins`
  (`configpasswordunmask` maskiert nur die Anzeige). DB-Zugriff eng begrenzen, Zugangsdaten
  regelmäßig rotieren, Rechte minimal halten (siehe getrennte Upload-Zugangsdaten).
- Signierte URLs sind bis zum Ablauf von jeder Person nutzbar, die sie kennt. Die
  Zugriffsprüfung auf den Abschnitt erfolgt beim Seitenaufruf in `play.php`; die URL
  erscheint danach im Seitenquelltext des angemeldeten Nutzers. Die Gültigkeit daher nicht
  größer als nötig wählen.
- Der Browser der Lernperson kontaktiert beim Abspielen den Storage-Anbieter direkt (IP-Adresse,
  Geräte-/Browserdaten). Es wird kein Moodle-Personenbezug (`$USER->id`) übertragen. Ein
  Auftragsverarbeitungsvertrag mit dem Storage-Anbieter ist erforderlich; bei EU-Standorten
  (z. B. Hetzner Falkenstein/Nürnberg/Helsinki) entfällt ein Drittlandtransfer.
- Details im Risikoregister in `DATENSCHUTZ.md`.
