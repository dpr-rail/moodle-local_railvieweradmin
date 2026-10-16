# Webservice „Railviewer API“ (`railviewer_api`)

Aktivierung: Website-Administration → Server → Webservices → Übersicht (Webservices + REST-Protokoll aktivieren, Token für einen
technischen Benutzer erzeugen). Der Benutzer braucht eine Rolle mit `local/railvieweradmin:assignroutes` (Zuweisen) und/oder
`local/railvieweradmin:viewprogress` (Fortschritt). Der Dienst ist auf berechtigte Benutzer beschränkt.

| Funktion | Recht | Zweck |
|---|---|---|
| `local_railvieweradmin_get_routes` | assignroutes | Strecken (id, Name, Gegenrichtung) |
| `local_railvieweradmin_get_methods` | assignroutes | Lernmethoden |
| `local_railvieweradmin_get_experience_types` | assignroutes | Berufserfahrungstypen |
| `local_railvieweradmin_assign_routes` | assignroutes | Strecken zuweisen: `userid`, `routeid`, `requiredviews` (ohne Lernmethoden), `experiencetypeid`, `waive`, `waivereason`. Rückgabe je Eintrag: `created`, `updated`, `exists` oder ein Fehlerstatus |
| `local_railvieweradmin_get_user_progress` | viewprogress | Stand je Strecke inkl. `missing[]`, `declaration`, `ordercomplete`, Soll-/Ist-Minuten |

Beispiel (REST/JSON):

```
POST {moodle}/webservice/rest/server.php
wstoken=…&wsfunction=local_railvieweradmin_get_user_progress&moodlewsrestformat=json&userid=42
```

Ereignisse: `\local_railvieweradmin\event\route_completed` und `…\all_routes_completed` (bei aktivierter Abschlusserklärung erst nach
deren Bestätigung). Optional als Webhook (Einstellungen), signiert mit HMAC-SHA256 im Header `X-Railviewer-Signature`.
