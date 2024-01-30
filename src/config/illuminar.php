<?php
return [
    'enabled' => env('ILLUMINAR_ENABLED', true),
    'theme' => 'light', // 'light' or 'dark'
    'storage' => [
        'driver'   => 'file',
        'path'     => storage_path('illuminar'),
        'filename' => 'illuminar',
        'limit'    => 2000, // Entries limit, old entries will be deleted
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
    'jobs'    => [
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
