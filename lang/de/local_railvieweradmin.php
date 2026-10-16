<?php
defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Railviewer Admin';

// Overlay Defaults Einstellungen
$string['overlay_defaults_settings'] = 'Overlay Standard-Einstellungen';
$string['overlay_defaults_settings_desc'] = 'Konfigurieren Sie die Standard-Einstellungen für Overlays im Rail Viewer.';

$string['overlay_default_fontsize'] = 'Standard-Schriftgröße';
$string['overlay_default_fontsize_desc'] = 'Standard-Schriftgröße für Overlay-Texte (in Pixel).';

$string['overlay_default_fontcolor'] = 'Standard-Schriftfarbe';
$string['overlay_default_fontcolor_desc'] = 'Standard-Schriftfarbe für Overlay-Texte (Hex-Code, z.B. #ff0000 für Rot).';

$string['overlay_default_backgroundcolor'] = 'Standard-Hintergrundfarbe';
$string['overlay_default_backgroundcolor_desc'] = 'Standard-Hintergrundfarbe für Overlays (Hex-Code, z.B. #ff0000 für Rot).';

$string['overlay_default_bordercolor'] = 'Standard-Rahmenfarbe';
$string['overlay_default_bordercolor_desc'] = 'Standard-Rahmenfarbe für Overlays (Hex-Code, z.B. #00ff00 für Grün).';

$string['overlay_default_markerposition'] = 'Standard-Marker-Position';
$string['overlay_default_markerposition_desc'] = 'Standard-Position für Overlay-Marker auf dem Bildschirm.';

// Marker-Positionen
$string['none'] = 'Keine';
$string['top-left'] = 'Oben links';
$string['top'] = 'Oben';
$string['top-right'] = 'Oben rechts';
$string['right'] = 'Rechts';
$string['bottom-right'] = 'Unten rechts';
$string['bottom'] = 'Unten';
$string['bottom-left'] = 'Unten links';
$string['left'] = 'Links';

// EBULA API Einstellungen
$string['ebula_api_heading'] = 'EBULA API Einstellungen';
$string['ebula_api_heading_desc'] = 'Konfiguration der EBULA API-Verbindungseinstellungen.';

$string['ebula_api_url'] = 'EBULA API URL';
$string['ebula_api_url_desc'] = 'Die vollständige URL zur EBULA API (z.B. https://api.ebula.com).';

$string['ebula_api_user'] = 'EBULA API Benutzer';
$string['ebula_api_user_desc'] = 'Benutzername für die EBULA API-Authentifizierung.';

$string['ebula_api_password'] = 'EBULA API Passwort';
$string['ebula_api_password_desc'] = 'Passwort für die EBULA API-Authentifizierung.';

$string['ebula_api_key'] = 'EBULA API Schlüssel';
$string['ebula_api_key_desc'] = 'API-Schlüssel für die EBULA API-Authentifizierung.';

// Vimeo API Einstellungen
$string['vimeo_api_settings'] = 'Vimeo API Einstellungen';
$string['vimeo_api_settings_desc'] = 'Konfiguration der Vimeo API-Verbindungseinstellungen.';

$string['vimeo_access_token'] = 'Vimeo Access Token';
$string['vimeo_access_token_desc'] = 'Access Token für die Vimeo API-Authentifizierung.';

$string['vimeo_client_id'] = 'Vimeo Client ID';
$string['vimeo_client_id_desc'] = 'Client ID für die Vimeo API-Anwendung.';

$string['vimeo_client_secret'] = 'Vimeo Client Secret';
$string['vimeo_client_secret_desc'] = 'Client Secret für die Vimeo API-Anwendung.';

// ignite.video API Einstellungen
$string['ignite_api_settings'] = 'ignite.video API Einstellungen';
$string['ignite_api_settings_desc'] = 'Konfiguration der ignite.video-Anbindung (alternative Videoquelle zu Vimeo).';
$string['ignite_api_key'] = 'ignite.video API-Schlüssel';
$string['ignite_api_key_desc'] = 'API-Schlüssel (Bearer-Token) aus den ignite.video "API Integrations"-Einstellungen.';
$string['ignite_embed_url_template'] = 'ignite.video Embed-URL-Muster';
$string['ignite_embed_url_template_desc'] = 'Vollständiges URL-Muster für den eingebetteten Player, mit {id} als Platzhalter für die Video-ID (z.B. https://play.ignite.video/player/index.html?id={id}&controls=false oder Ihre eigene Player-Domain).';
$string['ignite_allowed_statuses'] = 'Erlaubte ignite.video-Status';
$string['ignite_allowed_statuses_desc'] = 'Nur Videos mit einem der angehakten Status werden in der Videoauswahl angeboten. Ohne Haken wird ersatzweise nur "Fertig kodiert" verwendet.';
$string['ignite_status_complete'] = 'Fertig kodiert (COMPLETE)';
$string['ignite_status_progressing'] = 'Wird kodiert (PROGRESSING)';
$string['ignite_status_replacing'] = 'Datei wird ersetzt (REPLACING)';
$string['ignite_status_no_file'] = 'Noch keine Datei hochgeladen (NO_FILE)';
$string['ignite_status_error'] = 'Kodierung fehlgeschlagen (ERROR)';
$string['ignite_visibility_filter'] = 'Sichtbarkeit der ignite.video-Videos';
$string['ignite_visibility_filter_desc'] = 'Legt fest, welche Videos anhand ihrer Sichtbarkeit ("Aktiviert/Deaktiviert"-Schalter in ignite.video) in der Videoauswahl angeboten werden.';
$string['ignite_visibility_public_only'] = 'Nur öffentliche (public) Videos';
$string['ignite_visibility_private_only'] = 'Nur private/deaktivierte Videos';
$string['ignite_visibility_both'] = 'Öffentliche und private Videos';

// SharePoint API Einstellungen (siehe docs/sharepoint-einrichtung.md für die vollständige
// Einrichtungsanleitung für Administratoren)
$string['sharepoint_api_settings'] = 'SharePoint API Einstellungen';
$string['sharepoint_api_settings_desc'] = 'Konfiguration der SharePoint-Anbindung (weitere alternative Videoquelle zu Vimeo/ignite.video). Erfordert eine Azure AD/Entra-App-Registrierung mit Microsoft-Graph-Zugriff - siehe die mitgelieferte Einrichtungsanleitung (docs/sharepoint-einrichtung.md).';
$string['sharepoint_tenant_id'] = 'Tenant-ID (Verzeichnis-ID)';
$string['sharepoint_tenant_id_desc'] = 'Die Verzeichnis-ID (Tenant-ID) Ihres Microsoft-365/Entra-Mandanten, z.B. aus der Übersichtsseite der App-Registrierung in Azure AD.';
$string['sharepoint_client_id'] = 'Anwendungs-ID (Client-ID)';
$string['sharepoint_client_id_desc'] = 'Die Anwendungs-ID (Client-ID) der Azure-AD-App-Registrierung, über die dieses Plugin auf SharePoint zugreift.';
$string['sharepoint_client_secret'] = 'Client-Secret';
$string['sharepoint_client_secret_desc'] = 'Ein für diese App-Registrierung erstelltes Client-Secret (Zertifikate & Geheimnisse). Läuft je nach gewählter Gültigkeitsdauer irgendwann ab - rechtzeitig vor Ablauf erneuern.';
$string['sharepoint_site_url'] = 'SharePoint-Site-URL';
$string['sharepoint_site_url_desc'] = 'Vollständige URL der SharePoint-Site, in der die Videos liegen, z.B. https://ihrefirma.sharepoint.com/sites/Streckenvideos';
$string['sharepoint_library_name'] = 'Bibliotheksname (optional)';
$string['sharepoint_library_name_desc'] = 'Name der Dokument-/Videobibliothek innerhalb der Site, z.B. "Videos". Leer lassen, um die Standard-Dokumentbibliothek der Site zu verwenden.';
$string['sharepoint_test_token_failed'] = 'Anmeldung bei Microsoft Entra fehlgeschlagen. Bitte Tenant-ID, Anwendungs-ID und Client-Secret prüfen.';
$string['sharepoint_test_site_failed'] = 'Anmeldung erfolgreich, aber die SharePoint-Site wurde nicht gefunden. Bitte Site-URL prüfen und sicherstellen, dass die App-Berechtigung "Sites.Read.All" (Anwendungsberechtigung) mit Admin-Zustimmung freigegeben ist.';
$string['sharepoint_test_drive_failed'] = 'Site gefunden, aber die angegebene Bibliothek wurde darin nicht gefunden. Bitte Bibliotheksnamen prüfen (Groß-/Kleinschreibung wird ignoriert) oder leer lassen für die Standardbibliothek.';
$string['sharepoint_stream_error'] = 'Das Video konnte nicht von SharePoint geladen werden. Bitte einen Administrator informieren.';

// API Test
$string['api_test'] = 'API-Verbindungstests';
$string['test_connection'] = 'Verbindung testen';
$string['api_test_no_credentials'] = 'Keine Zugangsdaten hinterlegt.';
$string['api_test_success_vimeo'] = 'Verbindung erfolgreich! Angemeldet als: {$a}';
$string['api_test_success_ignite'] = 'Verbindung erfolgreich! Videos im Account: {$a}';
$string['api_test_success_ebula'] = 'Verbindung erfolgreich! Zugangsdaten akzeptiert (HTTP-Code: {$a}).';
$string['api_test_success_sharepoint'] = 'Verbindung erfolgreich! Anmeldung, Site und Bibliothek wurden erfolgreich aufgelöst.';
$string['api_test_failed_http'] = 'Verbindung fehlgeschlagen. HTTP-Code: {$a}';

// Sections Management
$string['manage_sections'] = 'Streckenabschnitte verwalten';
$string['sections'] = 'Streckenabschnitte';
$string['back_to_admin'] = 'Zurück zur Adminseite';
$string['create_new_section'] = 'Neuen Abschnitt anlegen';
$string['name'] = 'Bezeichnung';
$string['duration'] = 'Dauer';
$string['actions'] = 'Aktionen';
$string['edit'] = 'Bearbeiten';
$string['delete'] = 'Löschen';
$string['confirm_delete'] = 'Wirklich löschen?';
$string['confirm_delete_section_used'] = 'Achtung: Dieser Abschnitt wird noch von folgender(n) Strecke(n) verwendet: {$a->routes}. Diesen Strecken sind insgesamt {$a->assignments} Nutzer-Zuweisung(en) zugeordnet. Beim Löschen bleibt der Abschnitt als verwaiste (fehlende) Referenz in diesen Strecken zurück. Wirklich löschen?';
$string['section_used_in_routes'] = 'Verwendet in Strecke(n): {$a}';
$string['edit_overlays'] = 'Overlays bearbeiten';
$string['select_ebula_schedule'] = 'Ebula-Fahrplan auswählen';
$string['map_ebula_schedule_to_video'] = 'Ebula-Fahrplan auf Video mappen';

// Ebula Section Map
$string['map_timetable_to_video'] = 'Zuordnung Ebula->Video';
$string['current_time'] = 'Aktuelle Zeit';
$string['save_position'] = 'Position speichern';
$string['still_image_no_line_movement'] = 'Standbild (Linie nicht bewegen)';
$string['browser_no_video_support'] = 'Ihr Browser unterstützt das Video-Tag nicht.';
$string['browser_no_video_support_du'] = 'Dein Browser unterstützt das Video-Tag nicht.';
$string['browser_no_video_support_sie'] = 'Ihr Browser unterstützt das Video-Tag nicht.';
$string['no_video_found_for_section'] = 'Kein Video für diesen Abschnitt gefunden.';
$string['current_video'] = 'Aktuelles Video';
$string['ignite_encoding_label'] = 'Video wird bei ignite.video kodiert:';
$string['ignite_encoding_wait_notice'] = 'Diese Seite aktualisiert sich automatisch, sobald die Kodierung abgeschlossen ist.';
$string['ignite_encoding_error'] = 'Kodierung bei ignite.video fehlgeschlagen: {$a}';
$string['ignite_publish_permission_hint'] = 'Hinweis: "Öffentlich" funktioniert nur, wenn der hinterlegte ignite.video-API-Key zusätzlich zu "videos.create" auch die Berechtigung "videos.publish" hat. Fehlt sie, legt ignite.video das Video trotzdem als privat/deaktiviert an - laut deren eigener API-Dokumentation.';
$string['km'] = 'km';
$string['speed'] = 'Geschwindigkeit';
$string['station'] = 'Betriebsstelle';
$string['delete_error'] = 'Fehler beim Löschen';
$string['error_loading_mappings'] = 'Fehler beim Laden der Mappings';
$string['click_table_first_to_choose_position'] = 'Bitte zuerst auf die Tabelle klicken, um eine Position zu wählen.';
$string['point_saved'] = 'Punkt gespeichert!';
$string['save_error'] = 'Fehler beim Speichern';

// EBuLa Timetables
$string['ebula_timetables'] = 'EBuLa-Fahrpläne';
$string['edit_ebula'] = 'EBuLa-Fahrplan bearbeiten';
$string['ebula_xml_content'] = 'XML-Inhalt';
$string['ebula_updated'] = 'Fahrplan aktualisiert.';
$string['ebula_edit_linked_notice'] = 'Hinweis: {$a} Abschnitt(e) nutzen bereits einen aus diesem Fahrplan entnommenen Ausschnitt. Eine Änderung hier wirkt sich NICHT rückwirkend auf diese bereits gespeicherten Ausschnitte aus.';
$string['train_number_cannot_be_empty'] = 'Zugnummer darf nicht leer sein.';
$string['date_cannot_be_empty'] = 'Datum darf nicht leer sein.';
$string['train_number_max_5_digits'] = 'Zugnummer darf maximal 5 Stellen haben.';
$string['date_format_yyyy_mm_dd'] = 'Datum muss im Format YYYY-MM-DD sein.';
$string['zip_could_not_be_extracted'] = 'ZIP konnte nicht entpackt werden.';
$string['error_retrieving_data_http_code'] = 'Fehler beim Abrufen der Daten. HTTP-Code:';
$string['error_retrieving_data_response'] = 'Antwort der API:';
$string['retrieve_ebula_timetables'] = 'EBuLa Fahrpläne abrufen';
$string['ebula_data_imported_successfully'] = 'EBuLa-Daten erfolgreich importiert.';
$string['train_number_max_5_digits_label'] = 'Zugnummer (max 5 Stellen)';
$string['date_yyyy_mm_dd'] = 'Datum (YYYY-MM-DD)';
$string['retrieve'] = 'Abrufen';
$string['saved_entries'] = 'Gespeicherte Einträge';
$string['no_ebula_data_saved_yet'] = 'Noch keine EBuLa-Daten gespeichert.';
$string['train_number'] = 'Zugnummer';
$string['selection_string'] = 'Auswahlstring';
$string['uploaded_at'] = 'Hochgeladen am';
$string['linked_sections_col'] = 'Verlinkte Abschnitte';
$string['no_section_linked'] = 'Keinem Abschnitt zugeordnet';
$string['show'] = 'Anzeigen';

// Edit Section
$string['edit_section'] = 'Abschnitt bearbeiten';
$string['back_to_list'] = 'Zurück zur Liste';

// Index EBuLas
$string['ebula_timetable'] = 'EBuLa-Fahrplan';
$string['choose_ebula_timetable'] = 'EBuLa-Fahrplan wählen';
$string['assigned_timetable'] = 'Zugeordneter Fahrplan';
$string['no_timetable_assigned_yet'] = 'Bisher wurde kein Fahrplan zugeordnet';
$string['delete_assigned_timetable'] = 'Zugeordneten Fahrplan löschen';
$string['confirm_delete_this_section'] = 'Diesen Abschnitt wirklich löschen?';
$string['available_timetables'] = 'Vorhandene Fahrpläne';
$string['no_ebula_timetables_available_yet'] = 'Noch keine EBuLa-Fahrpläne vorhanden.';
$string['select'] = 'Auswählen';
$string['delete_existing_assignment_first'] = 'Sie müssen die bestehende Zuordnung erst löschen, bevor Sie eine neue Zuordnung vornehmen können';

// Allgemeine Strings
$string['save'] = 'Speichern';
$string['cancel'] = 'Abbrechen';
$string['delete'] = 'Löschen';
$string['edit'] = 'Bearbeiten';
$string['add'] = 'Hinzufügen';
$string['yes'] = 'Ja';
$string['no'] = 'Nein';
$string['none'] = 'Keiner';
$string['required_field'] = 'Pflichtfeld';

// Ebula bezogene Strings
$string['select_ebula'] = 'Ebula auswählen';
$string['show_ebula'] = 'Ebula anzeigen';
$string['select_ebula_for_section'] = 'Ebula auswählen für Abschnitt {$a}';
$string['save_selected_timetable'] = 'Markierten Fahrplan speichern';
$string['timetable_saved'] = 'Fahrplan gespeichert (ID: {$a})';
$string['save_error'] = 'Fehler beim Speichern: {$a}';

// XML-Validierungsstrings
$string['xml_string_empty'] = 'XML-String ist leer.';
$string['xml_parse_error'] = 'Fehler beim Parsen des XML:';
$string['xml_not_valid_schema'] = 'XML ist **nicht** valide gegen das Schema:';
$string['xml_valid_schema'] = 'XML-Datei ist gültig gemäß dem Schema.';
$string['xml_simplexml_load_error'] = 'XML konnte nicht mit SimpleXML geladen werden:';

// Tabellenköpfe und Inhalte
$string['bfpl_tables'] = 'BfplTabellen';
$string['overview'] = 'Übersicht';
$string['track_position'] = 'Gleislage: {$a}';
$string['km'] = 'km';
$string['speed'] = 'Geschwindigkeit';
$string['operating_point'] = 'Betriebsstelle';
$string['route'] = 'Strecke';
$string['routes'] = 'Strecken/Orte (Mehrfachauswahl möglich)';
$string['einsatzstelle'] = 'Einsatzstelle';
$string['einsatzstelle_assign'] = 'Einsatzstelle zuweisen';
$string['einsatzstelle_assign_help'] = 'Weist zusätzlich zu den oben einzeln ausgewählten Strecken/Orten alle Strecken/Orte dieser Einsatzstelle zu. Kann mit einer manuellen Auswahl kombiniert werden.';
$string['no_einsatzstelle'] = '– keine –';
$string['select_route_or_einsatzstelle'] = 'Bitte mindestens eine Strecke/einen Ort oder eine Einsatzstelle auswählen.';
$string['distance'] = 'Entfernung';
$string['type_entry'] = 'typeEntry';
$string['gradient'] = 'Neigung';
$string['radius'] = 'Radius';
$string['track_position_short'] = 'Gleislage';
$string['width'] = 'Breite';
$string['length'] = 'Länge';
$string['info'] = 'Info';
$string['saw_lines'] = 'Sägelinien';
$string['branch'] = 'Abzweigung';
$string['continuation'] = 'Fortsetzung';

// Gleislagen
$string['left'] = 'Links';
$string['right'] = 'Rechts';
$string['both_tracks'] = 'Beide Gleise';
$string['single_track'] = 'Einzelgleis';

// Overlay-Formular Strings
$string['overlay_name'] = 'Overlay Name';
$string['section'] = 'Abschnitt';
$string['start_time'] = 'Startzeitpunkt (Sekunden)';
$string['end_time'] = 'Endzeitpunkt (Sekunden)';
$string['position_x'] = 'Position X (% der Breite)';
$string['position_y'] = 'Position Y (% der Höhe)';
$string['show_advanced_options'] = 'Erweiterte Optionen anzeigen';
$string['hide_advanced_options'] = 'Erweiterte Optionen ausblenden';
$string['font_size'] = 'Schriftgröße (px)';
$string['font_color'] = 'Schriftfarbe (HEX)';
$string['background_color'] = 'Hintergrundfarbe (HEX)';
$string['border_color'] = 'Rahmenfarbe (HEX)';
$string['marker_position'] = 'Position des Pfeils';
$string['fixed_bubble_width'] = 'Feste Breite der Sprechblase (%)';

// Marker-Positionen
$string['no_arrow'] = 'Kein Pfeil';
$string['top_left'] = 'Oben links';
$string['top'] = 'Oben Mitte';
$string['top_right'] = 'Oben rechts';
$string['right'] = 'Rechts Mitte';
$string['bottom_right'] = 'Unten rechts';
$string['bottom'] = 'Unten Mitte';
$string['bottom_left'] = 'Unten links';
$string['left'] = 'Links Mitte';

// Validierungsnachrichten
$string['end_time_validation'] = 'Endzeitpunkt muss größer oder gleich Startzeitpunkt sein.';
$string['fixed_width_validation'] = 'Bitte geben Sie eine Zahl zwischen 0 und 100 ein oder lassen Sie das Feld leer.';
$string['name_required'] = 'Bitte geben Sie einen Namen ein.';
$string['vimeo_video_required'] = 'Bitte wählen Sie ein Vimeo-Video aus.';

// Section-Formular Strings
$string['name'] = 'Name';
$string['description'] = 'Beschreibung';
$string['video_source'] = 'Video-Quelle';
$string['upload_file'] = 'Datei hochladen';
$string['select_vimeo_video'] = 'Vimeo Video auswählen';
$string['select_ignite_video'] = 'ignite.video Video auswählen';
$string['select_ignite_video_label'] = 'ignite.video Video auswählen:';
$string['ignite_video_required'] = 'Bitte wählen Sie ein ignite.video-Video aus.';
$string['ignite_id'] = 'ignite.video ID: {$a}';
$string['select_sharepoint_video'] = 'SharePoint Video auswählen';
$string['select_sharepoint_video_label'] = 'SharePoint Video auswählen:';
$string['sharepoint_video_required'] = 'Bitte wählen Sie ein SharePoint-Video aus.';
$string['sharepoint_id'] = 'SharePoint-Videoelement ausgewählt';
$string['upload_video_file'] = 'Video-Datei hochladen';
$string['select_vimeo_video_label'] = 'Vimeo Video auswählen:';
$string['search_videos'] = 'Videos suchen...';
$string['load_videos'] = 'Videos laden';
$string['refresh_videos'] = 'Aktualisieren';
$string['loading_videos'] = 'Lade Videos...';
$string['no_description'] = 'Keine Beschreibung';
$string['created'] = 'Erstellt: {$a}';
$string['select_video'] = 'Auswählen';
$string['selected_video'] = 'Ausgewähltes Video:';
$string['vimeo_id'] = 'Vimeo ID: {$a}';
$string['previous'] = 'Zurück';
$string['next'] = 'Weiter';

// JavaScript-Meldungen
$string['js_timetable_saved'] = '✅ Fahrplan gespeichert (ID: {$a})';
$string['js_save_error'] = 'Fehler beim Speichern: {$a}';

// Fehlermeldungen
$string['error_loading_videos'] = 'Fehler beim Laden der Videos';
$string['error_parsing_response'] = 'Fehler beim Parsen der Antwort';
$string['error_loading_videos_status'] = 'Fehler beim Laden der Videos. Status: {$a}';

// Schriftgrößenoptionen (dynamisch generiert, aber Labels als Referenz)
$string['font_size_px'] = '{$a} px';

// Zusätzliche Kontext-Strings
$string['already_selected'] = 'Bereits ausgewählt';
$string['no_thumbnail'] = 'Kein Vorschaubild verfügbar';

// Sicherheits-/Fehlermeldungen für AJAX-Endpunkte
$string['invalid_session'] = 'Ungültige oder abgelaufene Sitzung. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
$string['generic_ajax_error'] = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
$string['invalid_data'] = 'Ungültige Daten.';

// Ebula-Section-Map (bisher fehlende Strings, wurden hartcodiert verwendet)
$string['no_input_received'] = 'Keine Daten empfangen.';
$string['json_decode_error'] = 'Die gesendeten Daten konnten nicht gelesen werden.';
$string['no_id_provided'] = 'Keine ID übergeben.';
$string['record_deleted'] = 'Datensatz gelöscht.';
$string['record_not_found'] = 'Datensatz nicht gefunden.';
$string['time_seconds'] = 'Zeit (Sek.)';
$string['position_px'] = 'Position (px)';
$string['is_static'] = 'Statisch?';
$string['no_points_saved'] = 'Keine Punkte gespeichert.';
$string['sectionid_missing'] = 'Abschnitts-ID fehlt.';

// Anrede-Einstellung (Du/Sie), gilt für beide Railviewer-Plugins
$string['addressing'] = 'Anrede';
$string['addressing_desc'] = 'Legt fest, ob Nutzerinnen und Nutzer im Railviewer-Aktivitätsmodul und in dieser Verwaltungsoberfläche geduzt oder gesiezt werden.';
$string['addressing_sie'] = 'Sie (förmlich)';
$string['addressing_du'] = 'Du (informell)';

$string['overlay_updated'] = 'Overlay erfolgreich aktualisiert';
$string['overlay_created'] = 'Overlay erfolgreich erstellt';
$string['section_saved'] = 'Abschnitt gespeichert.';

// Stopps (railviewer_stops)
$string['stops_for_section'] = 'Stopps für Abschnitt: {$a}';
$string['back_to_section_overview'] = 'Zurück zur Abschnitts-Übersicht';
$string['add_new_stop'] = 'Neuen Stopp hinzufügen';
$string['time_of_stop_seconds'] = 'Zeit des Stopps (Sek.)';
$string['no_stops_for_section'] = 'Keine Stopps für diesen Abschnitt vorhanden.';
$string['edit_stop'] = 'Stopp bearbeiten';
$string['create_new_stop'] = 'Neuen Stopp anlegen';
$string['stop_name'] = 'Name des Stopps';
$string['stop_time_seconds'] = 'Zeitpunkt des Stopps (Sekunden)';
$string['preview'] = 'Vorschau';
$string['stop_reached_default_name'] = 'Stopp erreicht';
$string['video_stopped_at_template'] = 'Video gestoppt bei "__NAME__" (__TIME__s).';
$string['stop_saved'] = 'Stopp gespeichert.';
$string['stoptime_must_be_positive'] = 'Der Zeitpunkt darf nicht negativ sein.';

// assignment_form.php / route_form.php
$string['user'] = 'Benutzer';
$string['users'] = 'Nutzer (Mehrfachauswahl möglich)';
$string['assignments_created_some_skipped'] = '{$a->created} Zuweisung(en) angelegt, {$a->skipped} übersprungen (waren dieser Strecke bereits zugewiesen).';
$string['assignments_all_skipped'] = 'Alle ausgewählten Nutzer waren dieser Strecke bereits zugewiesen - es wurde nichts geändert.';
$string['required_views'] = 'Anzahl Wiedergaben';
$string['assign_both_directions'] = 'Auch die Gegenrichtung zuweisen';
$string['assign_both_directions_help'] = 'Falls die gewählte Strecke als Hin-/Rückfahrt mit einer anderen Strecke verknüpft ist, wird diese Gegenrichtung mit derselben Anzahl Wiedergaben in einem Schritt mitzugewiesen. Hat die Strecke keine Gegenrichtung, hat diese Option keine Wirkung.';
$string['must_be_positive_number'] = 'Muss eine positive Zahl sein';
$string['select_sections_order'] = 'Abschnitte auswählen (Reihenfolge beachten)';
$string['select_at_least_one_section'] = 'Bitte mindestens einen Abschnitt auswählen.';

// index.php / assignments.php / edit_assignment.php / edit_route.php / index_overlays.php
$string['adminpagetitle'] = 'Railviewer Streckenverwaltung';
$string['adminpagedescription'] = 'Verwalten Sie Streckenabschnitte, fügen Sie Overlays hinzu und kombinieren Sie Abschnitte zu Strecken. Weisen Sie Strecken einzelnen Nutzern zu und definieren Sie, wie oft sie diese sehen müssen.';
$string['assemble_routes'] = 'Strecken zusammenstellen';
$string['manage_overlays_nav'] = 'Overlays verwalten';
$string['assign_routes_to_users'] = 'Strecken Usern zuweisen';
$string['tile_sections_desc'] = 'Videoabschnitte anlegen, Dauer und Quelle festlegen.';
$string['tile_routes_desc'] = 'Abschnitte in der richtigen Reihenfolge zu Strecken kombinieren.';
$string['tile_overlays_desc'] = 'Text-Einblendungen und Stopps zu Abschnitten hinzufügen.';
$string['tile_assignments_desc'] = 'Strecken einzelnen Nutzern zuweisen und Fortschritt einsehen.';
$string['tile_ebula_desc'] = 'EBuLa-Fahrplandaten von der Bahn-API abrufen und speichern.';
$string['playback_settings'] = 'Wiedergabe';
$string['playback_settings_desc'] = 'Einstellungen für die eigene Video-Fortschrittsleiste in mod_railviewer/play.php.';
$string['max_playback_speed'] = 'Maximale Wiedergabegeschwindigkeit';
$string['max_playback_speed_desc'] = 'Höchste Geschwindigkeitsstufe, die Nutzer im Player wählen können (bis 8-fach). Nur bei Upload- und Vimeo-Videos verfügbar - ignite.video unterstützt keine Geschwindigkeitsanpassung. "Aus" (1×) blendet den Regler komplett aus.';
$string['max_playback_speed_off'] = 'Aus (nur 1× - kein Regler)';
$string['show_mute_button'] = 'Stumm/Lautstärke-Regler anzeigen';
$string['show_mute_button_desc'] = 'Zeigt in der eigenen Video-Fortschrittsleiste einen Button zum Stummschalten/Entstummen an. Bei deaktivierter Einstellung läuft der Ton immer über die Standard-Lautstärke des Geräts, ohne eigene Stumm-Option im Player.';
$string['settings_tile_title'] = 'Einstellungen';
$string['tile_settings_desc'] = 'API-Zugangsdaten, Anrede, Kartenanbindung und weitere Plugin-Einstellungen.';
$string['manage_assignments'] = 'Strecken Zuweisungen verwalten';
$string['add_assignment'] = '➕ Zuweisung hinzufügen';
$string['assign_routes_to_user'] = '➕ Diesem User Strecken zuweisen';
$string['view_user_map'] = 'Karte ansehen';
$string['progress_col'] = 'Fortschritt';
$string['required_col'] = 'Notwendig';
$string['status'] = 'Status';
$string['status_done'] = 'Erledigt';
$string['status_open'] = 'Offen';
$string['stat_users'] = 'Nutzer';
$string['stat_assignments'] = 'Zuweisungen';
$string['stat_completed'] = 'Abgeschlossen';
$string['stat_open'] = 'Offen';
$string['search_assignments'] = 'Nach Nutzer suchen…';
$string['search_routes'] = 'Nach Name oder Abschnitt suchen…';
$string['search_routes_empty'] = 'Keine Strecken gefunden.';
$string['no_assignments_yet'] = 'Es sind noch keine Zuweisungen vorhanden.';
$string['round_trip_progress'] = '{$a->completed} / {$a->required} insgesamt (Hin + Rück)';
$string['edit_assignment_title'] = 'Zuweisung bearbeiten';
$string['create_new_assignment'] = 'Neue Zuweisung anlegen';
$string['manage_routes_title'] = 'Strecken verwalten';
$string['manage_einsatzstellen'] = 'Einsatzstellen verwalten';
$string['create_new_einsatzstelle'] = 'Neue Einsatzstelle anlegen';
$string['edit_einsatzstelle'] = 'Einsatzstelle bearbeiten';
$string['einsatzstelle_routes'] = 'Strecken/Orte dieser Einsatzstelle';
$string['einsatzstelle_saved'] = 'Einsatzstelle gespeichert.';
$string['no_einsatzstellen_yet'] = 'Es sind noch keine Einsatzstellen vorhanden.';
$string['select_at_least_one_route'] = 'Bitte mindestens eine Strecke/einen Ort auswählen.';
$string['tile_einsatzstellen_desc'] = 'Mehrere Strecken/Orte zu Einsatzstellen bündeln, um sie gemeinsam zuzuweisen.';
$string['create_new_route'] = 'Neue Strecke anlegen';
$string['route_sections_col'] = 'Abschnitte';
$string['missing_sections_warning'] = '{$a} fehlende(r) Abschnitt(e)';
$string['missing_sections_warning_title'] = 'Diese Strecke verweist auf mindestens einen Abschnitt, der nicht mehr existiert (vermutlich direkt gelöscht). Bitte in der Streckenbearbeitung bereinigen.';
$string['route_pairing_col'] = 'Gegenrichtung';
$string['paired_route'] = 'Gegenrichtung (verknüpfte Strecke)';
$string['paired_route_help'] = 'Verknüpft diese Strecke symmetrisch mit einer anderen, bereits bestehenden Strecke als deren Hin- bzw. Rückfahrt. Ist die gewählte Strecke bereits mit einer dritten Strecke verknüpft, wird diese alte Verknüpfung automatisch gelöst (die dritte Strecke bleibt dabei erhalten, wird nur wieder unverknüpft).';
$string['no_paired_route'] = '– keine –';
$string['route_direction'] = 'Fahrtrichtung';
$string['route_direction_required'] = 'Bitte eine Fahrtrichtung wählen, wenn eine Gegenrichtung verknüpft wird.';
$string['route_pairing_display'] = '{$a->direction}: verknüpft mit „{$a->partnername}"';
$string['route_unlinked'] = 'Verknüpfung gelöst.';
$string['unlink_route'] = 'Trennen';
$string['overlay_sections_title'] = 'Overlay-Abschnitte';
$string['overlay_count_col'] = 'Anzahl Overlays';
$string['create_new_overlay'] = 'Neues Overlay anlegen';
$string['assignment_saved'] = 'Zuweisung gespeichert.';
$string['route_saved'] = 'Strecke gespeichert.';
$string['edit_route'] = 'Strecke bearbeiten';
$string['fahrwegpunkt_art_we'] = 'WE';
$string['overlays_for_section'] = 'Overlays für Abschnitt: {$a}';
$string['no_overlays_for_section'] = 'Keine Overlays für diesen Abschnitt vorhanden.';
$string['edit_overlay'] = 'Overlay bearbeiten';

// Streckenkarten-Widget.
$string['map_settings'] = 'Kartenanbindung';
$string['map_settings_desc'] = 'Einstellungen für die optionale Streckenkarten-Ansicht (Leaflet-Karte mit dem deutschen Schienennetz).';
$string['map_enabled'] = 'Kartenansicht aktivieren';
$string['map_enabled_desc'] = 'Schaltet die gesamte Kartenfunktion (Admin-Kartenverwaltung und Kartenansicht für Nutzer) ein oder aus. Standardmäßig deaktiviert, bis Tile-Server und Geodaten geprüft wurden.';
$string['map_tile_url'] = 'Kartenkachel-URL';
$string['map_tile_url_desc'] = 'URL-Vorlage des Kartenkachel-Servers (WMTS/XYZ) mit den Platzhaltern {z}, {x}, {y}. Vorbelegt mit dem datenschutzkonformen TopPlusOpen-Dienst des Bundesamts für Kartographie und Geodäsie (BKG), kann aber jederzeit auf einen anderen Anbieter oder einen eigenen Server umgestellt werden.';
$string['map_tile_attribution'] = 'Attributionstext';
$string['map_tile_attribution_desc'] = 'Wird unten auf der Karte angezeigt. Bitte vor dem Produktivbetrieb gegen die aktuellen Nutzungsbedingungen des gewählten Kartendienstes prüfen.';
$string['map_tile_max_zoom'] = 'Maximaler Zoomlevel';
$string['map_tile_max_zoom_desc'] = 'Höchster von der Karte angeforderter Zoomlevel.';
$string['streckenmap'] = 'Streckenkarte';
$string['tile_streckenmap_desc'] = 'Videos per Kartenklick Streckenabschnitten oder Betriebsstellen zuordnen.';
$string['map_disabled_notice'] = 'Die Kartenansicht ist derzeit deaktiviert.';
$string['map_no_tileurl_notice'] = 'Es ist kein Kartenkachel-Server konfiguriert.';
$string['map_no_geo_sections_notice'] = 'Für Ihre zugewiesenen Strecken sind noch keine kartenbasierten Abschnitte hinterlegt.';
$string['streckenmap_admin_intro'] = 'Ordnen Sie Videos direkt auf der Karte einem Streckenabschnitt oder einer einzelnen Betriebsstelle zu.';
$string['streckenmap_selection_heading'] = 'Auswahl';
$string['streckenmap_admin_hint'] = 'Kanten anklicken, um eine zusammenhängende Kette für ein mehrteiliges Video zu bilden, oder direkt auf eine Betriebsstelle klicken für ein Ortskenntnis-Video.';
$string['streckenmap_current_chain'] = 'Aktuelle Kette';
$string['streckenmap_remove_last'] = 'Letzte Kante entfernen';
$string['streckenmap_discard_chain'] = 'Auswahl verwerfen';
$string['streckenmap_continue'] = 'Weiter';
$string['streckenmap_node_selected'] = 'Ausgewählte Betriebsstelle';
$string['streckenmap_legend_unassigned'] = 'Kein Video zugeordnet';
$string['streckenmap_legend_assigned'] = 'Video zugeordnet';
$string['streckenmap_legend_selected'] = 'Aktuell ausgewählt';
$string['streckenmap_chain_conflict'] = 'Ein Teil dieser Kette ist bereits einem anderen Abschnitt zugeordnet. Bitte Auswahl anpassen.';
$string['streckenmap_linked'] = 'Kartenverknüpfung';
$string['streckenmap_linked_edges'] = 'Dieser Abschnitt ist mit {$a} Kante(n) auf der Streckenkarte verknüpft.';
$string['streckenmap_linked_node'] = 'Dieser Abschnitt ist mit der Betriebsstelle „{$a}“ auf der Streckenkarte verknüpft (Ortskenntnis).';
$string['direction_hin'] = 'Hinfahrt';
$string['direction_rueck'] = 'Rückfahrt';
$string['route_label_short'] = 'Strecke';
$string['ortskenntnis'] = 'Ortskenntnis';

// Video-Upload direkt zu Vimeo/ignite.video (classes/forms/section_form.php)
$string['mode_search'] = 'Video suchen';
$string['mode_upload'] = 'Video hochladen';
$string['upload_title'] = 'Titel';
$string['upload_status'] = 'Status';
$string['upload_status_public'] = 'Öffentlich';
$string['upload_status_unlisted'] = 'Nicht gelistet';
$string['upload_status_private'] = 'Privat';
$string['upload_start'] = 'Hochladen';
$string['upload_size_hint'] = 'Hinweis: Die maximale Dateigröße pro Upload ist serverseitig begrenzt.';
$string['upload_in_progress'] = 'Video wird hochgeladen, bitte warten...';
$string['upload_success'] = 'Video erfolgreich hochgeladen und für diesen Abschnitt ausgewählt.';
$string['upload_title_required'] = 'Bitte einen Titel für das Video angeben.';
$string['upload_no_file'] = 'Bitte eine Video-Datei auswählen.';
$string['upload_file_too_large'] = 'Die Datei ist zu groß für den Upload (serverseitiges Limit überschritten).';
$string['upload_network_error'] = 'Netzwerkfehler beim Hochladen. Bitte Internetverbindung prüfen und erneut versuchen.';
$string['video_source_not_configured_suffix'] = '(nicht mehr konfiguriert)';
$string['upload_source_heading'] = 'Videoquelle: Datei-Upload';
$string['upload_source_heading_desc'] = 'Einstellungen zur Nutzung von Moodles eigenem Dateispeicher als Videoquelle für Streckenabschnitte.';
$string['enable_upload_source'] = 'Datei-Upload als Videoquelle anbieten';
$string['enable_upload_source_desc'] = 'Legt fest, ob beim Anlegen/Bearbeiten eines Abschnitts "Datei hochladen" (Moodles eigener Dateispeicher) als Videoquelle zur Auswahl steht. Im Regelfall richtet ein Admin nur eine einzige Videoquelle ein - deaktivieren Sie diese Option, wenn ausschließlich Vimeo/ignite.video/SharePoint genutzt werden sollen. Bereits bestehende Abschnitte, die Datei-Upload verwenden, bleiben davon unberührt.';

// S3 Object Storage.
$string['s3_settings'] = 'S3 Object Storage';
$string['s3_settings_desc'] = 'Videos in einem S3-kompatiblen Bucket (z.B. Hetzner Object Storage) speichern. Das Bucket sollte privat bleiben; die Wiedergabe erfolgt über kurzlebige signierte URLs. Für Browser-Uploads braucht das Bucket eine CORS-Regel, die PUT von der Origin dieser Seite erlaubt.';
$string['s3_endpoint'] = 'Endpoint';
$string['s3_endpoint_desc'] = 'z.B. https://fsn1.your-objectstorage.com';
$string['s3_region'] = 'Region';
$string['s3_region_desc'] = 'Wird für die Signatur benötigt, z.B. fsn1, nbg1 oder hel1 bei Hetzner.';
$string['s3_bucket'] = 'Bucket-Name';
$string['s3_bucket_desc'] = 'Name des Buckets mit den Videos.';
$string['s3_access_key'] = 'Access Key';
$string['s3_secret_key'] = 'Secret Key';
$string['s3_pathstyle'] = 'Path-Style-Adressierung';
$string['s3_pathstyle_desc'] = 'endpoint/bucket/key statt bucket.endpoint/key verwenden. Empfohlen.';
$string['s3_url_expiry_hours'] = 'Gültigkeit signierter URLs (Stunden)';
$string['s3_url_expiry_hours_desc'] = 'Wie lange Wiedergabe-URLs gültig bleiben (1-168). Sollte länger als die längste Sitzung sein.';
$string['s3_test_ok'] = 'Verbindung erfolgreich, Bucket ist erreichbar.';
$string['s3_not_configured'] = 'S3-Speicher ist nicht vollständig konfiguriert.';
$string['s3_request_failed'] = 'S3-Anfrage fehlgeschlagen: {$a}';
$string['s3_upload_invalid'] = 'Ungültiger Dateiname oder S3 nicht konfiguriert (erlaubt: mp4, m4v, webm, ogv, mov).';
$string['select_s3_video'] = 'S3 Object Storage';
$string['s3_select_label'] = 'Video aus S3-Bucket auswählen';
$string['s3_filter_prefix'] = 'Ordner / Präfix-Filter';
$string['s3_load_more'] = 'Mehr laden';
$string['s3_upload_label'] = 'Oder neues Video in das Bucket hochladen';
$string['s3_uploading'] = 'Wird hochgeladen...';
$string['s3_upload_failed'] = 'Upload fehlgeschlagen (CORS-Regel des Buckets prüfen).';
$string['s3_video_required'] = 'Bitte ein Video aus S3 auswählen oder hochladen.';
$string['s3_selected'] = 'Ausgewähltes S3-Objekt';
$string['s3_upload_access_key'] = 'Access Key nur für Uploads (optional)';
$string['s3_upload_secret_key'] = 'Secret Key nur für Uploads (optional)';
$string['s3_upload_keys_desc'] = 'Eigene Zugangsdaten für Browser-Uploads, z. B. ein Schlüssel nur mit Schreibrechten. Beide Felder leer lassen: Dann werden auch für Uploads die allgemeinen Zugangsdaten oben verwendet. Auflisten und Abspielen nutzen immer die allgemeinen Zugangsdaten.';

// Berechtigungen, Events, Webhook
$string['railvieweradmin:managesettings'] = 'Railviewer-Einstellungen verwalten (Zugangsdaten, Webhook)';
$string['railvieweradmin:editsections'] = 'Strecken/Abschnitte anlegen und bearbeiten, EBuLa-Fahrpläne abrufen';
$string['railvieweradmin:deletesections'] = 'Strecken/Abschnitte, Overlays und EBuLa-Daten löschen';
$string['railvieweradmin:manageeinsatzstellen'] = 'Einsatzstellen verwalten';
$string['railvieweradmin:assignroutes'] = 'Strecken Nutzern zuweisen';
$string['railvieweradmin:viewprogress'] = 'Bearbeitungsstand von Nutzern einsehen (API)';
$string['event_route_completed'] = 'Zugewiesene Strecke abgeschlossen';
$string['event_all_routes_completed'] = 'Alle zugewiesenen Strecken abgeschlossen';
$string['webhook_settings'] = 'Webhook (Fortschritts-Benachrichtigung)';
$string['webhook_settings_desc'] = 'Meldet per HTTP-POST (JSON), wenn ein Nutzer eine zugewiesene Strecke (route_completed) oder alle zugewiesenen Strecken (all_routes_completed) in der geforderten Anzahl abgeschlossen hat. Die Zustellung läuft über die Moodle-Cron-Aufgaben und wird bei Fehlern automatisch wiederholt.';
$string['webhook_url'] = 'Webhook-URL';
$string['webhook_url_desc'] = 'Ziel-URL für Fortschritts-Events. Leer lassen, um den Webhook zu deaktivieren.';
$string['webhook_secret'] = 'Webhook-Secret';
$string['webhook_secret_desc'] = 'Optional. Wenn gesetzt, steht im Header X-Railviewer-Signature "sha256=" + HMAC-SHA256 des Request-Bodys.';
$string['webhook_failed'] = 'Webhook-Zustellung fehlgeschlagen: {$a}';
$string['api_test_success_webhook'] = 'Verbindung erfolgreich! Ziel hat mit HTTP {$a} geantwortet.';

$string['learning_settings'] = 'Lernmethoden';
$string['learning_settings_desc'] = 'Zusatzfunktion: weitere Lernmethoden (Selbstfahren, Mitfahren, bei Nacht …) mit Ausbilder-Bestätigung, Anforderungsmatrix je Strecke und Berufserfahrungstyp sowie Compliance-Berichten.';
$string['learning_methods_enabled'] = 'Lernmethoden aktivieren';
$string['learning_methods_enabled_desc'] = 'Aus: es zählt wie bisher nur das Ansehen des Streckenvideos. An: Anforderungen (Video und weitere Methoden) werden je Strecke und Berufserfahrungstyp festgelegt und bei der Zuweisung übernommen.';
$string['compliance_mail_enabled'] = 'Abschlussbericht automatisch per E-Mail senden';
$string['compliance_mail_enabled_desc'] = 'Sobald ein Mitarbeiter alle zugewiesenen Strecken erfüllt hat, wird der Soll/Ist-Bericht als PDF an die unten genannten Adressen gesendet (einmal je Mitarbeiter).';
$string['compliance_mail_to'] = 'Empfänger-Adressen';
$string['compliance_mail_to_desc'] = 'Eine oder mehrere E-Mail-Adressen, durch Komma getrennt. Der Bericht enthält personenbezogene Daten und Unterschriften – nur interne Adressen eintragen.';
$string['compliance_mail_test_subject'] = 'Railviewer: Test des Compliance-Versands';
$string['compliance_mail_test_body'] = 'Dies ist eine Test-Nachricht der Railviewer-Einstellungen.';
$string['compliance_mail_test_ok'] = 'Test-Mail gesendet an: {$a}';
$string['compliance_mail_test_failed'] = 'Die Test-Mail konnte nicht gesendet werden.';
$string['learning_methods'] = 'Lernmethoden';
$string['learning_methods_desc'] = 'Weitere Lernmethoden neben dem Streckenvideo. Die geforderte Häufigkeit je Strecke und Berufserfahrungstyp legen Sie bei der jeweiligen Strecke fest.';
$string['learning_feature_disabled'] = 'Das Lernmethoden-Feature ist in den Plugin-Einstellungen nicht aktiviert.';
$string['method_night'] = 'Nachtfahrt';
$string['method_in_use'] = 'Die Methode wird bereits verwendet und kann nicht gelöscht werden. Deaktivieren Sie sie stattdessen.';
$string['add_method'] = 'Lernmethode hinzufügen';
$string['experience_types'] = 'Berufserfahrungstypen';
$string['experience_types_desc'] = 'Der Typ wird bei der Zuweisung gewählt und bestimmt, welche Anforderungen aus der Anforderungsmatrix der Strecke gelten.';
$string['experience_type_in_use'] = 'Der Typ wird bereits verwendet und kann nicht gelöscht werden. Deaktivieren Sie ihn stattdessen.';
$string['add_experience_type'] = 'Berufserfahrungstyp hinzufügen';
$string['sortorder'] = 'Reihenfolge';
$string['enabled'] = 'Aktiv';
$string['record_saved'] = 'Gespeichert.';

$string['route_requirements'] = 'Anforderungen';
$string['route_requirements_desc'] = 'Legt fest, was für diese Strecke gefordert wird: Anzahl je Lernmethode und Berufserfahrungstyp, Soll-Dauer je Abschnitt sowie Ersetzungsregeln. Änderungen gelten für neue Zuweisungen; bestehende behalten ihre bei der Zuweisung übernommenen Anforderungen.';
$string['back_to_routes'] = 'Zurück zu den Strecken';
$string['route_has_no_sections'] = 'Diese Strecke enthält noch keine Abschnitte.';
$string['method_video'] = 'Video';
$string['requirement_matrix'] = 'Anforderungsmatrix';
$string['requirement_matrix_desc'] = 'Geforderte Anzahl je Abschnitt (Video: Durchläufe der ganzen Strecke). Leer oder 0 = nicht gefordert.';
$string['experience_type'] = 'Berufserfahrungstyp';
$string['section_durations'] = 'Soll-Dauer je Abschnitt (Minuten pro Durchlauf)';
$string['section_durations_desc'] = 'Leer lassen, um die Videodauer des Abschnitts zu verwenden (grau angezeigt). Die Dauer wird am Abschnitt gespeichert und gilt für alle Strecken mit diesem Abschnitt.';
$string['substitution_rules'] = 'Ersetzungsregeln';
$string['substitution_rules_desc'] = 'Eine Fahrt der ersetzenden Methode zählt anteilig für die ersetzte Methode, aber nur mit Fahrten, die über die eigene Anforderung hinausgehen (z.B. 1× Nacht ersetzbar durch 2× Tag). Ohne Haken entscheidet der Nutzer selbst, ob er die Ersetzung anwendet.';
$string['subst_replaced'] = 'Ersetzbare Methode';
$string['subst_by'] = 'Ersetzt durch';
$string['subst_factor'] = 'Anzahl (N×)';
$string['subst_auto'] = 'Automatisch anwenden';
$string['subst_invalid'] = 'Ungültige Ersetzungsregel (gleiche Methode, fehlende Auswahl oder Anzahl kleiner 1).';
$string['subst_cycle'] = 'Gegenseitige Ersetzung (A durch B und B durch A) ist nicht erlaubt.';

$string['experience_type_help'] = 'Berufserfahrungstyp';
$string['experience_type_help_help'] = 'Der Typ bestimmt, welche Anforderungen aus der Anforderungsmatrix der jeweiligen Strecke für diese Zuweisung gelten.';
$string['assign_review_title'] = 'Zuweisung prüfen';
$string['assign_review_desc'] = 'Entfernen Sie den Haken bei Strecken, die nicht gefordert werden sollen (z.B. weil die Strecke bereits bekannt ist). Für abgewählte Strecken ist eine Begründung Pflicht; sie werden als „erlassen“ vermerkt und zählen als erfüllt.';
$string['assign_keep'] = 'Fordern';
$string['assign_existing_hint'] = 'Hinweis';
$string['assign_existing_merge'] = '{$a} Nutzer haben die Strecke schon – Anforderungen werden kombiniert (Maximum)';
$string['waive_reason'] = 'Begründung (bei Abwahl)';
$string['waive_reason_placeholder'] = 'z.B. kennt die Strecke bereits';
$string['waive_reason_required'] = 'Bitte eine Begründung für die abgewählte Strecke „{$a}“ angeben.';
$string['assign_confirm'] = 'Zuweisung durchführen';
$string['assignments_applied'] = 'Zuweisung durchgeführt: {$a->created} neu, {$a->merged} zusammengeführt, {$a->exists} unverändert.';

$string['add_ride'] = 'Fahrt erfassen';
$string['compliance_user_link'] = 'Compliance';
$string['declaration_enabled'] = 'Abschlusserklärung verlangen';
$string['declaration_enabled_desc'] = 'Nach Abarbeitung aller Lernfahrten muss der Lernende eine Erklärung bestätigen (oder ablehnen), bevor der gesamte Auftrag als erledigt gilt. Abschluss-Event, Webhook und Compliance-Mail folgen erst nach der Bestätigung.';
$string['declaration_text'] = 'Text der Abschlusserklärung';
$string['declaration_text_desc'] = 'Formatierter Text. Platzhalter: {firstname}, {lastname}, {fullname}, {idnumber}, {routes}, {einsatzstellen}, {duration} (Soll-Gesamtdauer), {date}.';
$string['declaration_text_default'] = '<p>Ich, {fullname}, erkläre, dass ich die Streckenkenntnis für die Strecken {routes} (Einsatzstellen: {einsatzstellen}) erworben habe und die Lernfahrten wahrheitsgemäß erfasst wurden.</p>';
$string['declaration_title'] = 'Abschlusserklärung';
$string['declaration_declined_hint'] = 'Sie haben die Erklärung abgelehnt. Der Auftrag gilt erst als erledigt, wenn Sie sie bestätigen.';
$string['declaration_decline_reason'] = 'Begründung bei Ablehnung (optional)';
$string['declaration_accept_btn'] = 'Erklärung bestätigen';
$string['declaration_decline_btn'] = 'Erklärung ablehnen';
$string['declaration_accepted_msg'] = 'Erklärung bestätigt. Der Auftrag ist erledigt.';
$string['declaration_declined_msg'] = 'Erklärung abgelehnt. Der Auftrag bleibt offen.';
$string['declaration_not_open'] = 'Es liegt aktuell keine zu beantwortende Erklärung vor.';
$string['order_complete'] = 'Der gesamte Auftrag ist erledigt.';
$string['duration_incomplete'] = '(Für einzelne Abschnitte/Methoden ist keine Dauer hinterlegt.)';
$string['duration_summary'] = 'Gesamtdauer Auftrag: Soll {$a->soll} · erbracht {$a->ist} · Rest {$a->rest} ({$a->percent} %)';
$string['matrix_missing'] = 'fehlt noch {$a}×';
$string['matrix_pending'] = '({$a} in Bestätigung)';
$string['matrix_substituted'] = '(+{$a} ersetzt)';
$string['video_progress'] = 'Streckenvideo: {$a->completed} / {$a->required} Durchläufe';
$string['still_missing'] = 'Es fehlen noch';
$string['waived_label'] = 'Erlassen';
$string['my_einsatzstellen'] = 'Meine Einsatzstellen';
$string['my_progress'] = 'Mein Fortschritt';
$string['my_rides'] = 'Meine Lernfahrten';
$string['my_rides_list'] = 'Erfasste Fahrten';
$string['no_rides_yet'] = 'Noch keine Fahrten erfasst.';
$string['rides_to_confirm'] = 'Zu bestätigende Fahrten';
$string['ride_confirm_title'] = 'Lernfahrt bestätigen';
$string['ride_already_answered'] = 'Diese Fahrt wurde bereits beantwortet.';
$string['ride_confirm_btn'] = 'Bestätigen';
$string['ride_reject_btn'] = 'Ablehnen';
$string['ride_reject_reason'] = 'Begründung bei Ablehnung (optional)';
$string['ride_rider'] = 'Fahrende Person';
$string['ride_sections'] = 'Befahrene Abschnitte';
$string['ride_method'] = 'Lernmethode';
$string['ride_date'] = 'Datum';
$string['ride_train'] = 'Zugnummer';
$string['ride_scope'] = 'Umfang';
$string['ride_scope_full'] = 'Gesamte Strecke';
$string['ride_scope_part'] = 'Teilabschnitt (von–bis)';
$string['ride_from'] = 'Von Abschnitt';
$string['ride_to'] = 'Bis Abschnitt';
$string['ride_trainer'] = 'Ausbilder';
$string['ride_trainer_placeholder'] = 'Name eingeben (mind. 2 Zeichen) …';
$string['ride_external'] = 'Ausbilder gehört zu einem anderen Eisenbahnverkehrsunternehmen (EVU)';
$string['ride_evu'] = 'EVU';
$string['ride_trainername'] = 'Name der anleitenden Person';
$string['ride_signature'] = 'Unterschrift der anleitenden Person';
$string['ride_signature_clear'] = 'Löschen';
$string['ride_save'] = 'Fahrt speichern';
$string['ride_saved_pending'] = 'Fahrt gespeichert. Der Ausbilder wurde zur Bestätigung benachrichtigt.';
$string['ride_saved_external'] = 'Fahrt mit Unterschrift gespeichert und gewertet.';
$string['ride_confirmed_msg'] = 'Fahrt bestätigt.';
$string['ride_rejected_msg'] = 'Fahrt abgelehnt.';
$string['ride_invalid'] = 'Ungültige Fahrt (Zuweisung oder Methode).';
$string['ride_invalid_range'] = 'Ungültiger Abschnittsbereich (Reihenfolge der Strecke beachten).';
$string['ride_invalid_train'] = 'Bitte eine Zugnummer (max. 30 Zeichen) angeben.';
$string['ride_invalid_date'] = 'Ungültiges Datum (nicht in der Zukunft).';
$string['ride_invalid_trainer'] = 'Bitte einen Ausbilder aus der Suche auswählen.';
$string['ride_external_required'] = 'Für externe Ausbilder sind EVU und Name erforderlich.';
$string['ride_signature_required'] = 'Bitte eine Unterschrift zeichnen.';
$string['ride_cannot_respond'] = 'Diese Fahrt kann von Ihnen nicht (mehr) beantwortet werden.';
$string['ride_status_pending'] = 'In Bestätigung';
$string['ride_status_confirmed'] = 'Bestätigt';
$string['ride_status_rejected'] = 'Abgelehnt';
$string['ride_status_revoked'] = 'Widerrufen';
$string['ride_message_subject'] = 'Bitte Lernfahrt bestätigen: {$a->rider} ({$a->date})';
$string['ride_message_body'] = '{$a->rider} hat eine Lernfahrt erfasst und Sie als Ausbilder angegeben.

Strecke: {$a->route}
Methode: {$a->method}
Datum: {$a->date}
Zugnummer: {$a->train}

Bitte bestätigen oder lehnen Sie die Fahrt hier ab: {$a->url}';
$string['ride_decision_confirmed'] = '{$a->trainer} hat Ihre Fahrt vom {$a->date} (Zug {$a->train}) bestätigt.';
$string['ride_decision_rejected'] = '{$a->trainer} hat Ihre Fahrt vom {$a->date} (Zug {$a->train}) abgelehnt.';
$string['messageprovider:ridestoconfirm'] = 'Lernfahrt zur Bestätigung';
$string['messageprovider:ridedecision'] = 'Entscheidung zu einer Lernfahrt';
$string['subst_apply_label'] = 'Ersatz: {$a->a} ersetzbar durch {$a->n}× {$a->b}';
$string['subst_on'] = 'angewendet';
$string['subst_off'] = 'nicht angewendet';
$string['subst_enable'] = 'Ersatz anwenden';
$string['subst_disable'] = 'Ersatz nicht anwenden';

$string['compliance_download_pdf'] = 'PDF herunterladen';
$string['compliance_filter_desc'] = 'Wählen Sie Berufserfahrungstypen und Einsatzstellen (leer = alle). Pro Typ entsteht ein eigener Abschnitt; im PDF folgen die Typen nacheinander.';
$string['compliance_generated'] = 'Erstellt am {$a}';
$string['compliance_mail_subject'] = 'Railviewer: Auftrag abgeschlossen – {$a->name}';
$string['compliance_mail_body'] = '{$a->name} hat am {$a->date} alle zugewiesenen Strecken abgeschlossen. Der Soll/Ist-Bericht ist als PDF angehängt.';
$string['compliance_mail_sent_msg'] = 'Bericht wurde gesendet.';
$string['compliance_mail_to_user'] = 'Bericht auch an die E-Mail-Adresse des Mitarbeiters senden';
$string['compliance_mail_to_user_desc'] = 'Zusätzlich zu den oben genannten Adressen geht der Abschlussbericht an die im Moodle-Profil des Mitarbeiters hinterlegte E-Mail-Adresse.';
$string['compliance_mail_failed'] = 'Der Compliance-Bericht konnte nicht gesendet werden.';
$string['compliance_preview'] = 'Vorschau';
$string['compliance_requirements_title'] = 'Compliance-Matrix (Soll-Anforderungen)';
$string['compliance_send_now'] = 'Bericht jetzt senden';
$string['compliance_soll_ist'] = 'Soll | Ist';
$string['compliance_substituted'] = '{$a} ersetzt';
$string['compliance_total_duration'] = 'Gesamtdauer Soll';
$string['compliance_user_pick'] = 'Mitarbeiter suchen (Name oder Benutzername):';
$string['compliance_user_title'] = 'Compliance Bericht Strecken-/Ortskenntnis Soll/Ist';
$string['declaration_status_accepted'] = 'Erklärung bestätigt am {$a}.';
$string['declaration_status_declined'] = 'Erklärung abgelehnt am {$a}.';
$string['declaration_status_none'] = 'Die geforderte Abschlusserklärung liegt noch nicht vor.';
$string['order_open'] = 'Der Auftrag ist noch nicht vollständig erledigt.';
$string['einsatzstellen'] = 'Einsatzstellen';
$string['ride_revoke'] = 'Widerrufen';
$string['ride_revoked_msg'] = 'Fahrt widerrufen.';
$string['rides_revoke_heading'] = 'Bestätigte Fahrten (Widerruf möglich)';

$string['tile_methods_desc'] = 'Weitere Lernmethoden (Selbstfahren, Mitfahren, bei Nacht …) pflegen.';
$string['tile_types_desc'] = 'Berufserfahrungstypen für die Zuweisung pflegen.';
$string['tile_compliance_desc'] = 'Soll-Matrix Strecken × Einsatzstellen × Lernmethoden als PDF.';
$string['tile_compliance_user_desc'] = 'Soll/Ist-Bericht eines Mitarbeiters ansehen, als PDF laden oder senden.';
$string['tile_import_desc'] = 'Stammdaten, Strecken, Anforderungen (Anzahl Lernfahrten), Ersetzungen, Einsatzstellen und Zuweisungen per CSV/ZIP importieren, inkl. Export zum Ergänzen.';

$string['import_sections_title'] = 'Abschnitte per CSV importieren';
$string['import_sections_desc'] = 'CSV mit Kopfzeile. Spalten: name (Pflicht), description, routename (stellt die Abschnitte in CSV-Reihenfolge zu dieser Strecke zusammen), video_source (vimeo, ignite, sharepoint oder s3) und video_ref (Vimeo-/ignite-ID, SharePoint „driveId|itemId“ bzw. S3-Pfad). Videos per Datei-Upload sind per CSV nicht möglich; Abschnitte ohne Video-Angabe können später ergänzt werden.';
$string['import_sample'] = 'Beispiel-CSV herunterladen';
$string['import_csvfile'] = 'CSV-Datei';
$string['import_delimiter'] = 'Trennzeichen';
$string['import_encoding'] = 'Zeichenkodierung';
$string['import_preview'] = 'Vorschau';
$string['import_preview_summary'] = '{$a->ok} von {$a->total} Zeilen sind importierbar.';
$string['import_run'] = 'Import durchführen';
$string['import_video_ref'] = 'Video-Verweis';
$string['import_err_name'] = 'Name fehlt.';
$string['import_err_source'] = 'Ungültige Videoquelle (erlaubt: vimeo, ignite, sharepoint, s3).';
$string['import_err_ref'] = 'Ungültiger Video-Verweis für Quelle „{$a}“.';
$string['import_err_file'] = 'Die Datei konnte nicht gelesen werden: {$a}';
$string['import_err_nocolumn'] = 'Die Spalte „name“ fehlt in der Kopfzeile.';
$string['import_warn_existing'] = 'Ein Abschnitt mit diesem Namen existiert bereits und wird wiederverwendet.';
$string['import_warn_duplicate_file'] = 'Name kommt in der Datei mehrfach vor und wird wiederverwendet.';
$string['import_warn_noref'] = 'Videoquelle ohne Verweis – Video wird ignoriert.';
$string['import_warn_video_ignored'] = 'Vorhandener Abschnitt: Video-Angabe wird nicht übernommen.';
$string['import_done'] = 'Import abgeschlossen: {$a->created} Abschnitte angelegt, {$a->reused} wiederverwendet, {$a->skipped} übersprungen, {$a->routescreated} Strecken neu angelegt.';

$string['declaration_open_hint'] = 'Bitte bestätigen Sie Ihre Abschlusserklärung, damit der Auftrag als erledigt gilt.';

$string['compliance_replaceable_by'] = 'ersetzbar durch';
$string['compliance_subst_user'] = 'Nutzer entscheidet';
$string['compliance_subst_auto'] = 'automatisch';

$string['dashboard_group_content'] = 'Strecken & Inhalte';
$string['dashboard_group_assign'] = 'Einsatzstellen & Zuweisung';
$string['dashboard_group_reports'] = 'Berichte & Compliance';
$string['dashboard_group_config'] = 'Einstellungen & Stammdaten';

$string['roundtrip_done_count'] = '{$a->done} von {$a->total} Richtungen erledigt';

$string['rides_lead'] = 'Erfassen Sie Ihre Lernfahrten und sehen Sie, welche Abschnitte noch fehlen.';
$string['stat_total_duration'] = 'Gesamtdauer Auftrag';
$string['stat_routes_done'] = 'Strecken erledigt';
$string['stat_pending_confirm'] = 'Wartet auf Bestätigung';
$string['stat_pending_confirm_sub'] = 'Ihre Fahrten beim Ausbilder';
$string['duration_incomplete_short'] = 'Dauer unvollständig';
$string['no_rideable_routes'] = 'Für Ihre Zuweisungen können aktuell keine Fahrten erfasst werden.';

$string['partner_route_hint'] = 'Diese Strecke ist mit der Gegenrichtung „{$a}“ verknüpft.';
$string['copy_partner_btn'] = 'Matrix und Ersetzungsregeln auf Gegenrichtung übernehmen';
$string['copy_partner_confirm'] = 'Die bestehenden Anforderungen und Ersetzungsregeln der Gegenrichtung werden überschrieben. Fortfahren?';
$string['copy_partner_done'] = 'Anforderungen und Ersetzungsregeln wurden auf die Gegenrichtung übernommen. Die Soll-Dauer ist abschnittsbezogen und muss dort separat gepflegt werden.';

$string['my_routes'] = 'Meine Strecken';
$string['gallery_sections_done'] = '{$a->done} von {$a->total} Abschnitten erfüllt';
$string['grid_hint'] = 'Markieren Sie in einer Zeile die Abschnitte, die Sie gefahren sind (Klicken oder Ziehen, nur zusammenhängend). Danach erscheint das Formular zum Erfassen der Fahrt.';
$string['grid_required_n'] = '{$a}× gefordert';
$string['grid_ride_n'] = 'Fahrt {$a}';
$string['grid_ride_extra'] = 'weitere Fahrt';
$string['grid_selection'] = 'Auswahl:';

$string['compliance_cell'] = 'Soll {$a->soll} | Ist {$a->ist}';
$string['compliance_cell_subst'] = '{$a} Ersatz';
$string['compliance_cell_pending'] = '{$a} offen';
$string['compliance_subst_applied'] = 'vom Mitarbeiter angewendet';
$string['compliance_subst_notapplied'] = 'vom Mitarbeiter nicht angewendet';
$string['compliance_no_rules'] = 'Keine Ersetzungsregeln.';
$string['compliance_subst_count'] = 'Anzahl ersetzt';
$string['compliance_subst_rides'] = 'Angerechnete Fahrten (Datum / Zugnummer)';
$string['compliance_subst_note'] = 'Als Ersatz gewertet für {$a->method} ({$a->section})';
$string['compliance_no_substitution_used'] = 'Es wurde keine Ersetzung angerechnet.';
$string['compliance_rides_heading'] = 'Erfasste Fahrten';
$string['compliance_recorded_on'] = 'Erfasst am';
$string['compliance_decision'] = 'Entscheidung / Hinweise';
$string['compliance_by_signature'] = 'per Unterschrift';

$string['declaration_esign_note'] = 'Die Unterschrift erfolgte elektronisch durch Bestätigung der Erklärung im System (Benutzer: {$a->user}, {$a->time}).';

$string['pdf_page_x_of_y'] = 'Seite {$a->page} von {$a->total}';
$string['compliance_idnumber'] = 'ID-Nr.';

$string['compliance_grid_legend'] = 'Grün = bestätigte Fahrt, gelb = wartet auf Bestätigung, ⇄ = als Ersatz für eine andere Lernmethode gewertet. Je Zeile eine Lernfahrt, der Balken überspannt die befahrenen Abschnitte.';

$string['import_data_title'] = 'Daten-Import (CSV/ZIP)';
$string['import_data_desc'] = 'Stammdaten, Strecken, Anforderungen (Anzahl Lernfahrten je Strecke und Lernmethode), Ersetzungen, Einsatzstellen und Zuweisungen lassen sich per CSV importieren – einzeln oder als ZIP mit mehreren Dateien (in der Reihenfolge der Tabelle). Es wird nur NEU angelegt: Vorhandenes wird übersprungen und nie überschrieben. Verweise auf andere Datensätze erfolgen über einen optionalen „key“ oder den Namen. Vor dem Schreiben gibt es immer eine Vorschau. Für Importe, die von bereits vorhandenen Daten abhängen, gibt es Exporte im Importformat: „Fehlendes ergänzen“ liefert eine Vorlage mit allen vorhandenen Verweisen und leeren Zellen zum Ausfüllen; „Bestand“ zeigt, was schon da ist. * = Pflichtspalte.';
$string['import_columns'] = 'Spalten';
$string['import_downloads'] = 'Vorlage / Export';
$string['import_dl_blank'] = 'Leere Vorlage';
$string['import_dl_missing'] = 'Fehlendes ergänzen';
$string['import_dl_existing'] = 'Bestand';
$string['import_all_zip'] = 'Alle Dateien als ZIP';
$string['import_upload'] = 'Datei hochladen';
$string['import_file'] = 'CSV- oder ZIP-Datei';
$string['import_filetype'] = 'Dateityp';
$string['import_filetype_help'] = 'Dateityp';
$string['import_filetype_help_help'] = '„Automatisch“ erkennt den Typ am Dateinamen (z. B. 04_strecken.csv oder anforderungen.csv). Bei ZIP-Dateien wird jede Datei einzeln am Namen erkannt.';
$string['import_type_auto'] = 'Automatisch';
$string['import_type_unknown'] = 'Dateityp von „{$a}“ nicht erkannt – bitte Dateityp wählen oder die Datei umbenennen.';
$string['import_file_ignored'] = 'Datei „{$a}“ im ZIP ignoriert (unbekannter Name oder zu groß).';
$string['import_zip_failed'] = 'Das ZIP konnte nicht gelesen werden.';
$string['import_type_methoden'] = 'Lernmethoden';
$string['import_type_erfahrungstypen'] = 'Berufserfahrungstypen';
$string['import_type_abschnitte'] = 'Abschnitte (inkl. Video-Verweis)';
$string['import_type_strecken'] = 'Strecken (inkl. Hin-/Rückfahrt-Verknüpfung)';
$string['import_type_strecken_abschnitte'] = 'Abschnitte je Strecke (Reihenfolge)';
$string['import_type_abschnitt_dauer'] = 'Soll-Dauer je Abschnitt und Methode';
$string['import_type_anforderungen'] = 'Anforderungen: Anzahl Lernfahrten je Strecke, Berufserfahrungstyp und Methode';
$string['import_type_ersetzungen'] = 'Ersetzungsregeln';
$string['import_type_einsatzstellen'] = 'Einsatzstellen';
$string['import_type_einsatzstellen_strecken'] = 'Strecken je Einsatzstelle';
$string['import_type_zuweisungen'] = 'Zuweisungen (Nutzer – Strecke/Einsatzstelle)';
$string['import_preview_note'] = 'Vorschau – es wurde noch nichts gespeichert. Prüfen Sie die Meldungen und starten Sie dann den Import.';
$string['import_counts_preview'] = '{$a->created} würden angelegt, {$a->skipped} übersprungen, {$a->errors} Fehler';
$string['import_counts_done'] = '{$a->created} angelegt, {$a->skipped} übersprungen, {$a->errors} Fehler';
$string['import_line'] = 'Zeile';
$string['import_message'] = 'Meldung';
$string['import_status_created'] = 'Neu';
$string['import_status_skipped'] = 'Übersprungen';
$string['import_status_error'] = 'Fehler';
$string['import_more_rows'] = '… {$a} weitere Zeilen';
$string['import_again'] = 'Weiteren Import starten';
$string['import_nothing_new'] = 'Es gibt nichts Neues anzulegen.';
$string['import_session_expired'] = 'Die Importdaten sind abgelaufen oder gehören zu einer anderen Sitzung. Bitte Datei erneut hochladen.';
$string['import_no_dryrun'] = 'Der Import benötigt eine Datenbank mit Transaktionen (InnoDB), um die Vorschau sicher zu erzeugen. Dieses System erfüllt das nicht.';
$string['import_missing_columns'] = 'Pflichtspalte(n) fehlen: {$a}';
$string['import_unknown_column'] = 'Unbekannte Spalte „{$a}“ wird ignoriert.';
$string['import_exists'] = 'Existiert bereits – übersprungen.';
$string['import_empty_row'] = 'Keine Angabe zum Ausfüllen – übersprungen.';
$string['import_ambiguous'] = '„{$a}“ ist nicht eindeutig (mehrere Treffer) – bitte einen key verwenden.';
$string['import_not_found'] = '{$a->what} „{$a->ref}“ nicht gefunden.';
$string['import_type_required'] = 'Berufserfahrungstyp fehlt (es gibt mehrere aktive Typen).';
$string['import_err_ril'] = 'Für location_type „node“ ist ril100 erforderlich.';
$string['import_err_direction'] = 'Ungültige Richtung (erlaubt: hin, rueck).';
$string['import_err_selfpair'] = 'Eine Strecke kann nicht mit sich selbst verknüpft werden.';
$string['import_err_alreadypaired'] = 'Strecke oder Gegenrichtung ist bereits verknüpft.';
$string['import_route_has_sections'] = 'Strecke hat bereits Abschnitte – unverändert.';
$string['import_err_duplicate_section'] = 'Abschnitt kommt in der Strecke mehrfach vor.';
$string['import_err_minutes'] = 'Minuten müssen eine ganze Zahl ab 1 sein.';
$string['import_err_count'] = 'Ungültige Anzahl „{$a}“ (ganze Zahl ab 0).';
$string['import_requirements_summary'] = '{$a->new} Werte angelegt, {$a->old} bereits vorhanden';
$string['import_assign_summary'] = '{$a->new} Zuweisung(en) angelegt, {$a->old} bereits vorhanden';
$string['import_einsatz_empty'] = 'Die Einsatzstelle enthält keine Strecken.';
$string['import_err_waive_reason'] = 'Beim Erlassen ist eine Begründung (waive_reason) Pflicht.';

$string['rides_to_confirm_n'] = '{$a} Lernfahrt(en) warten auf Ihre Bestätigung';
$string['rides_to_confirm_more'] = '… {$a} weitere anzeigen';

$string['import_type_not_allowed'] = 'Für „{$a}“ fehlt Ihnen das erforderliche Recht – Datei ignoriert.';
