<?php
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
