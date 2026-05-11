<?php

declare(strict_types=1);

return [
    'generate' => [
        'route'                 => [
            'actions'      => env('WAYFINDER_GENERATE_ROUTE_ACTIONS', true),
            'named'        => env('WAYFINDER_GENERATE_NAMED_ROUTES', false),
            'form_variant' => env('WAYFINDER_GENERATE_FORM_VARIANT', true),
            'ignore'       => [
                'urls'  => [],
                'names' => ['debugbar.*', 'admin.*', 'boost.*'],
            ],
        ],
        'models'                => env('WAYFINDER_GENERATE_MODELS', false),
        'inertia'               => [
            'shared_data' => env('WAYFINDER_GENERATE_INERTIA_SHARED_DATA', false),
            'component'   => env('WAYFINDER_GENERATE_INERTIA_COMPONENT', true),
        ],
        'broadcast'             => [
            'channels' => env('WAYFINDER_GENERATE_BROADCAST_CHANNELS', true),
            'events'   => env('WAYFINDER_GENERATE_BROADCAST_EVENTS', true),
        ],
        'environment_variables' => env('WAYFINDER_GENERATE_ENVIRONMENT_VARIABLES', true),
        'enums'                 => env('WAYFINDER_GENERATE_ENUMS', true),
    ],

    // Format the generated files
    'format' => [
        'enabled' => env('WAYFINDER_FORMAT_ENABLED', false),
    ],

    'cache' => [
        'enabled'   => env('WAYFINDER_CACHE_ENABLED', true),
        'directory' => env('WAYFINDER_CACHE_DIRECTORY', storage_path('wayfinder-cache')),
    ],
];
