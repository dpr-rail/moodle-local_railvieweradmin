# Sicherheit – Überblick und Prüfstand

Stand der Durchsicht: Version 1.5.0. Es handelt sich um eine **manuelle Code-Durchsicht** der neuen und geänderten Dateien,
**kein Penetrationstest** und kein Lauf eines automatischen Scanners. PHPUnit-Tests liegen vor (`tests/`), wurden aber nicht in
einer vollständigen Moodle-Testumgebung ausgeführt.

## Geprüft und umgesetzt
* **Zugriff:** Jede Seite verlangt `require_login()` und ein passendes Recht (`editsections`, `deletesections`,
  `manageeinsatzstellen`, `assignroutes`, `viewprogress`, `managesettings`). Der Daten-Import prüft das Recht **je Dateityp**
  (Zuweisungen: `assignroutes`, Einsatzstellen: `manageeinsatzstellen`), ebenso die Exporte.
* **CSRF:** Alle schreibenden Aktionen verlangen `sesskey` (eigene Formulare, Ajax, Löschlinks); Moodle-Formulare tun das automatisch.
* **Objektzugriff:** Fahrten und Ersetzungsentscheidungen werden nur für die eigene Zuweisung angenommen; die Bestätigung nur
  durch den benannten Ausbilder und nur im Status „ausstehend“. Unterschriften sind nur für Fahrenden, Ausbilder und Berechtigte abrufbar.
* **SQL:** Ausschließlich Platzhalter/Moodle-DML; Suchen nutzen `sql_like` mit Escaping.
* **XSS:** Ausgabe über `s()`/`format_string()`; Nachrichten werden als escaped HTML erzeugt; JSON für Inline-JS über `json_encode`.
  Erklärungstexte laufen durch `format_text()` (bereinigtes HTML).
* **Dateien:** Unterschriften werden als PNG geprüft (Typ, Größe, Bilddaten) und nur als Download ausgeliefert. Der ZIP-Import liest Einträge
  nur in den Speicher (kein Entpacken auf die Platte, daher kein „Zip Slip“), begrenzt Dateianzahl und Größe; Temp-Dateien liegen unter einem
  zufälligen, an die Sitzung gebundenen Token.
* **CSV-/Formel-Injection:** Exporte stellen Zellen, die mit `=`, `+` oder `@` beginnen, ein Apostroph voran; der Import entfernt es wieder.
* **Import-Vorschau:** Läuft als Transaktion mit Rollback; auf Datenbanken ohne Transaktionen (z. B. MyISAM) ist der Import gesperrt.
* **Webhook/Mail:** Ziel-URL und Empfänger sind nur über die Einstellungen (`managesettings`) änderbar; HTTP-Zugriffe nutzen Moodles `curl`-Klasse
  (respektiert die Sperrlisten für Hosts/Ports). Secrets liegen als Passwort-Einstellung.

## Bewusste Entscheidungen / Restrisiken
* **Ausbilder-Tippsuche:** Jeder angemeldete Nutzer kann Namen aktiver Nutzer finden (ab 2 Zeichen, max. 20 Treffer). Das ist fachlich gewollt
  („jeder Nutzer kann Ausbilder sein“), ermöglicht aber das Aufzählen von Nutzernamen. Bei Bedarf auf eine Rolle/Gruppe einschränken.
* **Externe Ausbilder:** Eine Fahrt mit Unterschrift zählt sofort; Name/EVU/Unterschrift sind nicht verifizierbar. Admins können Fahrten
  widerrufen (`assignroutes`).
* **Webservice:** Token sind wie Passwörter zu behandeln; technischen Benutzer mit minimalen Rechten verwenden, Token-IP-Beschränkung nutzen.
* **Berichte/Mails:** PDFs enthalten personenbezogene Daten und Unterschriften – nur interne Empfänger eintragen, Mail-Transportverschlüsselung sicherstellen.
* **Nicht geprüft:** Browser-Verhalten der Raster-Auswahl (Touch/Maus), Lastverhalten bei sehr großen Importen, Drittbibliotheken (TCPDF aus dem Moodle-Core).

## Empfehlung vor dem Produktivbetrieb
Test auf einer Kopie (Upgrade, Import-Vorschau, Rollen), Rollen mit den fünf Einzelrechten sparsam vergeben, Backup vor dem ersten Import,
Datenschutz-Dokument (`DATENSCHUTZ.md`) mit dem Datenschutzbeauftragten abstimmen.
