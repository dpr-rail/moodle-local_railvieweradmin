<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_railvieweradmin_get_routes' => [
        'classname' => 'local_railvieweradmin_external',
        'methodname' => 'get_routes',
        'classpath' => 'local/railvieweradmin/externallib.php',
        'description' => 'Lists all routes (id, name, paired route) so they can be referenced in assignments.',
        'type' => 'read',
        'capabilities' => 'local/railvieweradmin:assignroutes',
    ],
    'local_railvieweradmin_assign_routes' => [
        'classname' => 'local_railvieweradmin_external',
        'methodname' => 'assign_routes',
        'classpath' => 'local/railvieweradmin/externallib.php',
        'description' => 'Assigns routes to users with a required number of runs.',
        'type' => 'write',
        'capabilities' => 'local/railvieweradmin:assignroutes',
    ],
    'local_railvieweradmin_get_user_progress' => [
        'classname' => 'local_railvieweradmin_external',
        'methodname' => 'get_user_progress',
        'classpath' => 'local/railvieweradmin/externallib.php',
        'description' => 'Returns the progress of a user on all assigned routes.',
        'type' => 'read',
        'capabilities' => 'local/railvieweradmin:viewprogress',
    ],
    'local_railvieweradmin_get_methods' => [
        'classname' => 'local_railvieweradmin_external',
        'methodname' => 'get_methods',
        'classpath' => 'local/railvieweradmin/externallib.php',
        'description' => 'Lists the learning methods.',
        'type' => 'read',
        'capabilities' => 'local/railvieweradmin:assignroutes',
    ],
    'local_railvieweradmin_get_experience_types' => [
        'classname' => 'local_railvieweradmin_external',
        'methodname' => 'get_experience_types',
        'classpath' => 'local/railvieweradmin/externallib.php',
        'description' => 'Lists the experience types.',
        'type' => 'read',
        'capabilities' => 'local/railvieweradmin:assignroutes',
    ],
];

$services = [
    'Railviewer API' => [
        'functions' => [
            'local_railvieweradmin_get_routes',
            'local_railvieweradmin_assign_routes',
            'local_railvieweradmin_get_user_progress',
            'local_railvieweradmin_get_methods',
            'local_railvieweradmin_get_experience_types',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'railviewer_api',
    ],
];
