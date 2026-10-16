<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Part of the local_railvieweradmin plugin.
 *
 * @package    local_railvieweradmin
 * @copyright  dpr-rail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
