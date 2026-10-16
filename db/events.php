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

// Beide Fortschritts-Events zusätzlich per Webhook nach außen melden (nur aktiv, wenn in den
// Plugin-Einstellungen eine Webhook-URL hinterlegt ist). Andere Plugins können dieselben Events
// über ihre eigene db/events.php abonnieren.
$observers = [
    [
        'eventname' => '\local_railvieweradmin\event\route_completed',
        'callback' => '\local_railvieweradmin\observer::progress_event',
    ],
    [
        'eventname' => '\local_railvieweradmin\event\all_routes_completed',
        'callback' => '\local_railvieweradmin\observer::progress_event',
    ],
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_railvieweradmin\observer::user_deleted',
    ],
];
