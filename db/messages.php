<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Ein Fahrender bittet den Ausbilder, eine Lernfahrt zu bestätigen oder abzulehnen.
    'ridestoconfirm' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
        ],
    ],
    // Rückmeldung des Ausbilders an den Fahrenden.
    'ridedecision' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
];
