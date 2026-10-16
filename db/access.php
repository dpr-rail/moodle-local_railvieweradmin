<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Berechtigungen von local_railvieweradmin.
 *
 * Das frühere Sammelrecht local/railvieweradmin:manage wurde durch fünf Einzelrechte ersetzt.
 * Alle sind System-Kontext-Rechte. Website-Administratoren besitzen sie implizit; für andere
 * Rollen müssen sie unter Website-Administration -> Nutzer -> Rechte -> Rollen definieren
 * vergeben werden.
 */
$capabilities = [

    // Plugin-Einstellungen (Zugangsdaten für EBuLa/Vimeo/ignite/SharePoint/S3, Webhook).
    // Bewusst ohne Archetyp: nur Website-Administratoren, außer ein Admin vergibt es explizit.
    'local/railvieweradmin:managesettings' => [
        'riskbitmask' => RISK_CONFIG | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    // Strecken/Abschnitte, Overlays, Stopps anlegen und bearbeiten sowie EBuLa-Fahrpläne
    // abrufen, bearbeiten und Abschnitten zuordnen.
    'local/railvieweradmin:editsections' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Strecken/Abschnitte, Overlays, Stopps und EBuLa-Daten löschen.
    'local/railvieweradmin:deletesections' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Einsatzstellen (Gruppen von Strecken) anlegen, bearbeiten und löschen.
    'local/railvieweradmin:manageeinsatzstellen' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Strecken Nutzern zuweisen (Weboberfläche und Webservice), Zuweisungen ändern/entfernen.
    'local/railvieweradmin:assignroutes' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Bearbeitungsstand von Nutzern einsehen (Webservice).
    'local/railvieweradmin:viewprogress' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

];
