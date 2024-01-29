<?php
return [
    'enabled' => env('ILLUMINAR_ENABLED', false),
    'storage' => [
        'driver'        => 'file',
        'path'          => storage_path('illuminar'),
        'filename'      => 'illuminar',
    ],
    'queries' => [
        'slow_time_ms'  => 5000,
        'ignored_paths' => [
            'vendor/october/rain',
            'vendor/barryvdh/laravel-debugbar',
            'vendor/laravel' // This will ignore all Laravel framework queries
        ]
    ],
    'events'  => [
        'ignore_framework_events' => true,
        'ignored_events'          => []
    ],
    'jobs' => [
        'ignored_jobs' => []
    ],
    'model'   => [
        'trackable_events' => [
            'restored',
            'updated',
            'created',
            'deleted'
        ]
    ]
];
