<?php
defined('MOODLE_INTERNAL') || die;

// Prüfe ob das Plugin aktiviert ist
$pluginman = core_plugin_manager::instance();
$plugin = $pluginman->get_plugin_info('local_railvieweradmin');
$enabled = $plugin && $plugin->is_enabled();

if ($hassiteconfig || has_capability('local/railvieweradmin:managesettings', context_system::instance())) {
    // Fester Name statt der $section-Variable: anders als bei Aktivitätsmodulen (mod_*, deren
    // Plugin-Loader $section selbst über get_settings_section_name() setzt) übergibt der
    // Loader für lokale Plugins (core\plugininfo\local::load_settings()) gar kein $section an
    // diese Datei - die Variable wäre hier schlicht undefiniert. Das führte dazu, dass die
    // Einstellungsseite unter einem unvorhersehbaren/leeren Namen registriert wurde und
    // /admin/settings.php?section=local_railvieweradmin mit "Section error!" fehlschlug. Ein
    // fest verdrahteter, mit dem Component-Namen identischer Name macht die Seite zuverlässig
    // unter genau dieser URL erreichbar (z.B. für den Verweis auf der Plugin-Startseite).
    $section = 'local_railvieweradmin';
    $settings = new admin_settingpage($section, get_string('pluginname', 'local_railvieweradmin'), 'local/railvieweradmin:managesettings', !$enabled);

    if ($ADMIN->fulltree) {

        // Test-Button direkt am Ende des jeweiligen Abschnitts (EBULA, Vimeo, ignite.video,
        // SharePoint, S3). Inline-Styles statt einer eigenen CSS-Klasse: styles.css wird auf der
        // Admin-Einstellungsseite (Moodle-eigener Renderer) nicht eingebunden.
        $addtestbutton = function (string $servicekey) use ($settings) {
            $settings->add(new admin_setting_heading(
                'local_railvieweradmin/' . $servicekey . '_test_heading',
                '',
                '<div class="railviewer-api-test" data-service="' . $servicekey . '" style="margin-bottom: 14px;">'
                . '<button type="button" class="btn btn-secondary railviewer-api-test-button">'
                . get_string('test_connection', 'local_railvieweradmin')
                . '</button>'
                . '<div class="railviewer-api-test-result" style="margin-top: 6px;"></div>'
                . '</div>'
            ));
        };

        // Anrede (Du/Sie) — gilt für dieses Plugin und für mod_railviewer
        $settings->add(new admin_setting_configselect(
            'local_railvieweradmin/addressing',
            get_string('addressing', 'local_railvieweradmin'),
            get_string('addressing_desc', 'local_railvieweradmin'),
            'sie',
            [
                'sie' => get_string('addressing_sie', 'local_railvieweradmin'),
                'du' => get_string('addressing_du', 'local_railvieweradmin'),
            ]
        ));

        // Overlay Defaults Einstellungen Header
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/overlay_defaults_heading',
            get_string('overlay_defaults_settings', 'local_railvieweradmin'),
            get_string('overlay_defaults_settings_desc', 'local_railvieweradmin')
        ));

        // Overlay default font size
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/overlay_default_fontsize',
            get_string('overlay_default_fontsize', 'local_railvieweradmin'),
            get_string('overlay_default_fontsize_desc', 'local_railvieweradmin'),
            '',
            PARAM_INT
        ));

        // Overlay default font color
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/overlay_default_fontcolor',
            get_string('overlay_default_fontcolor', 'local_railvieweradmin'),
            get_string('overlay_default_fontcolor_desc', 'local_railvieweradmin'),
            '#ff0000',
            PARAM_RAW // Rohtext, damit # erlaubt ist
        ));

        // Overlay default background-color
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/overlay_default_backgroundcolor',
            get_string('overlay_default_backgroundcolor', 'local_railvieweradmin'),
            get_string('overlay_default_backgroundcolor_desc', 'local_railvieweradmin'),
            '#ff0000',
            PARAM_RAW
        ));

        // Overlay default border-color
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/overlay_default_bordercolor',
            get_string('overlay_default_bordercolor', 'local_railvieweradmin'),
            get_string('overlay_default_bordercolor_desc', 'local_railvieweradmin'),
            '#00ff00',
            PARAM_RAW
        ));

        $markerpositions = [
            'none' => get_string('none', 'local_railvieweradmin'),
            'top-left' => get_string('top-left', 'local_railvieweradmin'),
            'top' => get_string('top', 'local_railvieweradmin'),
            'top-right' => get_string('top-right', 'local_railvieweradmin'),
            'right' => get_string('right', 'local_railvieweradmin'),
            'bottom-right' => get_string('bottom-right', 'local_railvieweradmin'),
            'bottom' => get_string('bottom', 'local_railvieweradmin'),
            'bottom-left' => get_string('bottom-left', 'local_railvieweradmin'),
            'left' => get_string('left', 'local_railvieweradmin'),
        ];

        $settings->add(new admin_setting_configselect(
            'local_railvieweradmin/overlay_default_markerposition',
            get_string('overlay_default_markerposition', 'local_railvieweradmin'),
            get_string('overlay_default_markerposition_desc', 'local_railvieweradmin'),
            'none',
            $markerpositions
        ));

        // Streckenkarte (Leaflet-Kartenansicht) Einstellungen
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/map_heading',
            get_string('map_settings', 'local_railvieweradmin'),
            get_string('map_settings_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/map_enabled',
            get_string('map_enabled', 'local_railvieweradmin'),
            get_string('map_enabled_desc', 'local_railvieweradmin'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/map_tile_url',
            get_string('map_tile_url', 'local_railvieweradmin'),
            get_string('map_tile_url_desc', 'local_railvieweradmin'),
            'https://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/web/default/WEBMERCATOR/{z}/{y}/{x}.png',
            // Kein PARAM_URL: das ist eine Leaflet-Kachel-URL-Vorlage mit {z}/{y}/{x}-Platzhaltern,
            // keine direkt aufrufbare URL. Geschweifte Klammern sind laut RFC 3986 in einer URL
            // nicht erlaubt, PARAM_URL verwirft den Wert deshalb als "ungültig".
            PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/map_tile_attribution',
            get_string('map_tile_attribution', 'local_railvieweradmin'),
            get_string('map_tile_attribution_desc', 'local_railvieweradmin'),
            '&copy; <a href="https://www.bkg.bund.de" target="_blank" rel="noopener">Bundesamt für Kartographie und Geodäsie</a> ' . date('Y') . ', Datenquellen: basemap.de/BKG',
            PARAM_RAW
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/map_tile_max_zoom',
            get_string('map_tile_max_zoom', 'local_railvieweradmin'),
            get_string('map_tile_max_zoom_desc', 'local_railvieweradmin'),
            19,
            PARAM_INT
        ));

        // Wiedergabe-Einstellungen
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/playback_heading',
            get_string('playback_settings', 'local_railvieweradmin'),
            get_string('playback_settings_desc', 'local_railvieweradmin')
        ));

        // Maximal erlaubte Wiedergabegeschwindigkeit im eigenen Player (siehe play.php). Default
        // "1" = Regler ausgeblendet/kein Schnellvorlauf möglich, bis der Admin bewusst eine
        // höhere Stufe freigibt - konsistent mit den übrigen, standardmäßig restriktiven
        // Feature-Schaltern dieses Plugins (z.B. map_enabled).
        $settings->add(new admin_setting_configselect(
            'local_railvieweradmin/max_playback_speed',
            get_string('max_playback_speed', 'local_railvieweradmin'),
            get_string('max_playback_speed_desc', 'local_railvieweradmin'),
            '1',
            [
                '1' => get_string('max_playback_speed_off', 'local_railvieweradmin'),
                '1.25' => '1,25×',
                '1.5' => '1,5×',
                '2' => '2×',
                '3' => '3×',
                '4' => '4×',
                '6' => '6×',
                '8' => '8×',
            ]
        ));

        // Stumm/Lautstärke-Button ein-/ausblenden. Default an (übliche Player-Bedienung) -
        // anders als beim Geschwindigkeitsregler gibt es hier keinen "Umgehungs"-Aspekt, den
        // ein restriktiver Default rechtfertigen würde.
        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/show_mute_button',
            get_string('show_mute_button', 'local_railvieweradmin'),
            get_string('show_mute_button_desc', 'local_railvieweradmin'),
            1
        ));

        // Videoquelle "Datei-Upload" (Moodles eigener Dateispeicher) - anders als Vimeo/ignite/
        // SharePoint braucht diese Quelle keine externen Zugangsdaten und war deshalb bisher
        // immer verfügbar. Jetzt ebenfalls ein Schalter: im Regelfall richtet ein Admin nur EINE
        // Videoquelle ein (z.B. ausschließlich Vimeo) - "Upload" soll dann in der Auswahl beim
        // Anlegen eines Abschnitts NICHT mehr als scheinbare Option auftauchen. Default AN, damit
        // bestehende Installationen beim Update dieses Plugins ihr bisheriges Verhalten (Upload
        // war immer die Standardquelle) unverändert behalten.
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/upload_source_heading',
            get_string('upload_source_heading', 'local_railvieweradmin'),
            get_string('upload_source_heading_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/enable_upload_source',
            get_string('enable_upload_source', 'local_railvieweradmin'),
            get_string('enable_upload_source_desc', 'local_railvieweradmin'),
            1
        ));

        // Lernmethoden-Feature (Selbstfahren/Mitfahren, Ausbilder-Bestätigung, Anforderungsmatrix,
        // Compliance-Berichte). Default aus: dann gilt unverändert das reine Video-Zählen.
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/learning_heading',
            get_string('learning_settings', 'local_railvieweradmin'),
            get_string('learning_settings_desc', 'local_railvieweradmin')
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/learning_methods_enabled',
            get_string('learning_methods_enabled', 'local_railvieweradmin'),
            get_string('learning_methods_enabled_desc', 'local_railvieweradmin'),
            0
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/declaration_enabled',
            get_string('declaration_enabled', 'local_railvieweradmin'),
            get_string('declaration_enabled_desc', 'local_railvieweradmin'),
            0
        ));
        $settings->add(new admin_setting_confightmleditor(
            'local_railvieweradmin/declaration_text',
            get_string('declaration_text', 'local_railvieweradmin'),
            get_string('declaration_text_desc', 'local_railvieweradmin'),
            get_string('declaration_text_default', 'local_railvieweradmin')
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/compliance_mail_enabled',
            get_string('compliance_mail_enabled', 'local_railvieweradmin'),
            get_string('compliance_mail_enabled_desc', 'local_railvieweradmin'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/compliance_mail_to',
            get_string('compliance_mail_to', 'local_railvieweradmin'),
            get_string('compliance_mail_to_desc', 'local_railvieweradmin'),
            '',
            PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_railvieweradmin/compliance_mail_to_user',
            get_string('compliance_mail_to_user', 'local_railvieweradmin'),
            get_string('compliance_mail_to_user_desc', 'local_railvieweradmin'),
            0
        ));
        $addtestbutton('compliancemail');

        // EBULA API Einstellungen Header
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/ebula_api_heading',
            get_string('ebula_api_heading', 'local_railvieweradmin'),
            get_string('ebula_api_heading_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/ebula_api_url',
            get_string('ebula_api_url', 'local_railvieweradmin'),
            get_string('ebula_api_url_desc', 'local_railvieweradmin'),
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/ebula_api_user',
            get_string('ebula_api_user', 'local_railvieweradmin'),
            get_string('ebula_api_user_desc', 'local_railvieweradmin'),
            '',
            PARAM_ALPHANUMEXT
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/ebula_api_password',
            get_string('ebula_api_password', 'local_railvieweradmin'),
            get_string('ebula_api_password_desc', 'local_railvieweradmin'),
            ''
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/ebula_api_key',
            get_string('ebula_api_key', 'local_railvieweradmin'),
            get_string('ebula_api_key_desc', 'local_railvieweradmin'),
            ''
        ));
        $addtestbutton('ebula');


        // Vimeo API Einstellungen Header
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/vimeo_api_heading',
            get_string('vimeo_api_settings', 'local_railvieweradmin'),
            get_string('vimeo_api_settings_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/vimeo_access_token',
            get_string('vimeo_access_token', 'local_railvieweradmin'),
            get_string('vimeo_access_token_desc', 'local_railvieweradmin'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/vimeo_client_id',
            get_string('vimeo_client_id', 'local_railvieweradmin'),
            get_string('vimeo_client_id_desc', 'local_railvieweradmin'),
            '',
            PARAM_ALPHANUMEXT
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/vimeo_client_secret',
            get_string('vimeo_client_secret', 'local_railvieweradmin'),
            get_string('vimeo_client_secret_desc', 'local_railvieweradmin'),
            ''
        ));
        $addtestbutton('vimeo');


        // ignite.video API Einstellungen
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/ignite_api_heading',
            get_string('ignite_api_settings', 'local_railvieweradmin'),
            get_string('ignite_api_settings_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/ignite_api_key',
            get_string('ignite_api_key', 'local_railvieweradmin'),
            get_string('ignite_api_key_desc', 'local_railvieweradmin'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/ignite_embed_url_template',
            get_string('ignite_embed_url_template', 'local_railvieweradmin'),
            get_string('ignite_embed_url_template_desc', 'local_railvieweradmin'),
            // Das White-Label-/CDN-Domain dieses konkreten ignite.video-Accounts, nicht die
            // generische ignite.video-Domain aus deren eigenen Docs-Beispielen - bestätigt per
            // funktionierendem Embed-Beispiel und direkt getestet (HTTP 200). Das gesamte
            // URL-Muster (nicht nur die Domain) ist konfigurierbar, damit sich z.B. Pfad oder
            // Player-Parameter (controls, autoplay, ...) künftig ohne Code-Änderung anpassen
            // lassen.
            'https://railsky.euvideocdn.com/player/index.html?id={id}&controls=false',
            // Kein PARAM_URL: {id} ist ein Platzhalter mit geschweiften Klammern, die laut
            // RFC 3986 in einer URL nicht erlaubt sind - PARAM_URL würde den Wert verwerfen
            // (siehe derselbe Fix schon bei map_tile_url weiter oben).
            PARAM_RAW_TRIMMED
        ));

        // Welche ignite.video-Status- und Sichtbarkeits-Werte in der Videoauswahl auftauchen
        // dürfen (siehe ajax/get_ignite_videos.php) - bisher hart auf COMPLETE/public verdrahtet.
        $ignitestatusoptions = [
            'COMPLETE' => get_string('ignite_status_complete', 'local_railvieweradmin'),
            'PROGRESSING' => get_string('ignite_status_progressing', 'local_railvieweradmin'),
            'REPLACING' => get_string('ignite_status_replacing', 'local_railvieweradmin'),
            'NO_FILE' => get_string('ignite_status_no_file', 'local_railvieweradmin'),
            'ERROR' => get_string('ignite_status_error', 'local_railvieweradmin'),
        ];
        $settings->add(new admin_setting_configmulticheckbox(
            'local_railvieweradmin/ignite_allowed_statuses',
            get_string('ignite_allowed_statuses', 'local_railvieweradmin'),
            get_string('ignite_allowed_statuses_desc', 'local_railvieweradmin'),
            ['COMPLETE' => 1],
            $ignitestatusoptions
        ));

        // Sichtbarkeits-Filter für die ignite.video-Auswahl: nur öffentliche, nur private, oder
        // beide - eine reine Ja/Nein-Checkbox ("auch private zeigen") ließ "nur private zeigen"
        // gar nicht erst zu.
        $settings->add(new admin_setting_configselect(
            'local_railvieweradmin/ignite_show_private',
            get_string('ignite_visibility_filter', 'local_railvieweradmin'),
            get_string('ignite_visibility_filter_desc', 'local_railvieweradmin'),
            'public',
            [
                'public' => get_string('ignite_visibility_public_only', 'local_railvieweradmin'),
                'private' => get_string('ignite_visibility_private_only', 'local_railvieweradmin'),
                'both' => get_string('ignite_visibility_both', 'local_railvieweradmin'),
            ]
        ));
        $addtestbutton('ignite');


        // SharePoint API Einstellungen (App-only Microsoft-Graph-Zugriff per Azure-AD/Entra-
        // App-Registrierung) - siehe docs/sharepoint-einrichtung.md für die vollständige
        // Einrichtungsanleitung für Administratoren.
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/sharepoint_api_heading',
            get_string('sharepoint_api_settings', 'local_railvieweradmin'),
            get_string('sharepoint_api_settings_desc', 'local_railvieweradmin')
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/sharepoint_tenant_id',
            get_string('sharepoint_tenant_id', 'local_railvieweradmin'),
            get_string('sharepoint_tenant_id_desc', 'local_railvieweradmin'),
            '',
            PARAM_RAW_TRIMMED // GUID oder Domain-Name, kann Bindestriche/Punkte enthalten.
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/sharepoint_client_id',
            get_string('sharepoint_client_id', 'local_railvieweradmin'),
            get_string('sharepoint_client_id_desc', 'local_railvieweradmin'),
            '',
            PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/sharepoint_client_secret',
            get_string('sharepoint_client_secret', 'local_railvieweradmin'),
            get_string('sharepoint_client_secret_desc', 'local_railvieweradmin'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/sharepoint_site_url',
            get_string('sharepoint_site_url', 'local_railvieweradmin'),
            get_string('sharepoint_site_url_desc', 'local_railvieweradmin'),
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/sharepoint_library_name',
            get_string('sharepoint_library_name', 'local_railvieweradmin'),
            get_string('sharepoint_library_name_desc', 'local_railvieweradmin'),
            '',
            PARAM_TEXT
        ));
        $addtestbutton('sharepoint');


        // S3 Object Storage (z.B. Hetzner)
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/s3_heading',
            get_string('s3_settings', 'local_railvieweradmin'),
            get_string('s3_settings_desc', 'local_railvieweradmin')
        ));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_endpoint',
            get_string('s3_endpoint', 'local_railvieweradmin'), get_string('s3_endpoint_desc', 'local_railvieweradmin'),
            '', PARAM_URL));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_region',
            get_string('s3_region', 'local_railvieweradmin'), get_string('s3_region_desc', 'local_railvieweradmin'),
            'fsn1', PARAM_ALPHANUMEXT));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_bucket',
            get_string('s3_bucket', 'local_railvieweradmin'), get_string('s3_bucket_desc', 'local_railvieweradmin'),
            '', PARAM_RAW_TRIMMED));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_access_key',
            get_string('s3_access_key', 'local_railvieweradmin'), '', '', PARAM_RAW_TRIMMED));
        $settings->add(new admin_setting_configpasswordunmask('local_railvieweradmin/s3_secret_key',
            get_string('s3_secret_key', 'local_railvieweradmin'), '', ''));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_upload_access_key',
            get_string('s3_upload_access_key', 'local_railvieweradmin'), get_string('s3_upload_keys_desc', 'local_railvieweradmin'), '', PARAM_RAW_TRIMMED));
        $settings->add(new admin_setting_configpasswordunmask('local_railvieweradmin/s3_upload_secret_key',
            get_string('s3_upload_secret_key', 'local_railvieweradmin'), '', ''));
        $settings->add(new admin_setting_configcheckbox('local_railvieweradmin/s3_pathstyle',
            get_string('s3_pathstyle', 'local_railvieweradmin'), get_string('s3_pathstyle_desc', 'local_railvieweradmin'), 1));
        $settings->add(new admin_setting_configtext('local_railvieweradmin/s3_url_expiry_hours',
            get_string('s3_url_expiry_hours', 'local_railvieweradmin'), get_string('s3_url_expiry_hours_desc', 'local_railvieweradmin'),
            '12', PARAM_INT));
        $addtestbutton('s3');

        // Webhook: meldet route_completed / all_routes_completed per HTTP POST an ein Drittsystem.
        $settings->add(new admin_setting_heading(
            'local_railvieweradmin/webhook_heading',
            get_string('webhook_settings', 'local_railvieweradmin'),
            get_string('webhook_settings_desc', 'local_railvieweradmin')
        ));
        $settings->add(new admin_setting_configtext(
            'local_railvieweradmin/webhook_url',
            get_string('webhook_url', 'local_railvieweradmin'),
            get_string('webhook_url_desc', 'local_railvieweradmin'),
            '',
            PARAM_URL
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_railvieweradmin/webhook_secret',
            get_string('webhook_secret', 'local_railvieweradmin'),
            get_string('webhook_secret_desc', 'local_railvieweradmin'),
            ''
        ));
        $addtestbutton('webhook');

        // JavaScript für die Test-Buttons nur auf der eigenen Einstellungsseite einbetten.
        global $PAGE;
        if (optional_param('section', '', PARAM_TEXT) === $section) {
            $js = "
            require(['jquery'], function($) {
                $(document).ready(function() {
                    $('.railviewer-api-test-button').on('click', function() {
                        var wrapper = $(this).closest('.railviewer-api-test');
                        var service = wrapper.data('service');
                        var resultDiv = wrapper.find('.railviewer-api-test-result');
                        var button = $(this);

                        button.prop('disabled', true);
                        resultDiv.html('" . get_string('test_connection', 'local_railvieweradmin') . " …');

                        $.ajax({
                            url: M.cfg.wwwroot + '/local/railvieweradmin/test_api.php',
                            type: 'GET',
                            data: {service: service},
                            dataType: 'json',
                            success: function(data) {
                                if (data.success) {
                                    resultDiv.html('<div class=\"alert alert-success\">' + data.message + '</div>');
                                } else {
                                    resultDiv.html('<div class=\"alert alert-danger\">' + data.message + '</div>');
                                }
                            },
                            error: function(xhr, status, error) {
                                resultDiv.html('<div class=\"alert alert-danger\">' + error + '</div>');
                            },
                            complete: function() {
                                button.prop('disabled', false);
                            }
                        });
                    });
                });
            });
            ";

            $PAGE->requires->js_init_code($js);
        }
    }

    $ADMIN->add('localplugins', $settings);
}
