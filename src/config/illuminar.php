<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable / disable Illuminar
    |--------------------------------------------------------------------------
    |
    | When disabled, every illuminar() call becomes a no-op and the UI returns 404.
    | By default it is enabled only for the "local" environment.
    |
    */
    'enabled' => env('ILLUMINAR_ENABLED', env('APP_ENV') === 'local'),

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'path'       => env('ILLUMINAR_PATH', 'illuminar'),
    'domain'     => env('ILLUMINAR_DOMAIN'),
    'middleware' => ['web'],
    'theme'      => env('ILLUMINAR_THEME', 'dark'), // 'light' or 'dark'

    /*
    | Who can open the UI. If a "viewIlluminar" gate is defined, it is used.
    | Otherwise the UI is available only in the environments listed here.
    */
    'allowed_environments' => ['local'],

    /*
    |--------------------------------------------------------------------------
    | Watchers enabled for every request / command / job
    |--------------------------------------------------------------------------
    |
    | No need to call illuminar()->trackQueries() in code: switch a watcher on here
    | (or via .env) and it will be active from the application boot.
    |
    */
    'track' => [
        'query'        => env('ILLUMINAR_TRACK_QUERIES', false),
        'slow_query'   => env('ILLUMINAR_TRACK_SLOW_QUERIES', false),
        'model'        => env('ILLUMINAR_TRACK_MODELS', false),
        'job'          => env('ILLUMINAR_TRACK_JOBS', false),
        'event'        => env('ILLUMINAR_TRACK_EVENTS', false),
        'cache'        => env('ILLUMINAR_TRACK_CACHE', false),
        'mail'         => env('ILLUMINAR_TRACK_MAILS', false),
        'http_request' => env('ILLUMINAR_TRACK_HTTP_REQUESTS', false),
        'exception'    => env('ILLUMINAR_TRACK_EXCEPTIONS', true),
        'log'          => env('ILLUMINAR_TRACK_LOGS', false),
        'request'      => env('ILLUMINAR_TRACK_REQUESTS', true), // summary of every request / command / job
    ],

    /*
    |--------------------------------------------------------------------------
    | Open-in-editor links for callers
    |--------------------------------------------------------------------------
    |
    | Supported: phpstorm, vscode, cursor, sublime, zed, idea, or a custom template
    | with %file and %line placeholders. Set to null to disable links.
    | For Docker / Sail / VM set remote & local paths to map container paths.
    |
    */
    'editor'            => env('ILLUMINAR_EDITOR', 'phpstorm'),
    'editor_remote_path' => env('ILLUMINAR_REMOTE_PATH'),
    'editor_local_path'  => env('ILLUMINAR_LOCAL_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Dumps
    |--------------------------------------------------------------------------
    */
    'dump_depth'      => 2,     // How many levels of array/object are expanded by default
    'dump_max_items'  => 2500,  // Max items cloned per dump (protects from huge collections)
    'dump_max_string' => 10000, // Max string length per dump (-1 for unlimited)

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | driver: "sqlite" (indexed, recommended), "file" (JSON lines) or a class name
    | implementing StorageDriverInterface. "sqlite" falls back to "file" when pdo_sqlite is missing.
    |
    */
    'storage' => [
        'driver'        => env('ILLUMINAR_STORAGE', 'sqlite'),
        'path'          => storage_path('illuminar'),
        'filename'      => 'illuminar',
        'limit'         => 2000, // Entries limit, old entries will be deleted
        'max_file_size' => 20,   // MB, "file" driver: storage file is compacted when it grows bigger
    ],

    'requests' => [
        // The same SQL executed at least this many times during one request is reported as possible N+1
        'n_plus_one_threshold' => 3,
        'ignored_paths'        => ['telescope*', 'horizon*', '_debugbar*', 'favicon.ico'],
        'ignored_commands'     => [
            'illuminar:*',
            'queue:work',
            'queue:listen',
            'horizon*',
            'schedule:work',
            'octane:*',
            'reverb:*',
            'serve',
            'tinker',
            'pail',
        ],
    ],

    'logs' => [
        'min_level' => 'debug', // debug, info, notice, warning, error, critical, alert, emergency
    ],

    'queries' => [
        'slow_time_ms'  => 500,
        'ignored_paths' => [
            'vendor/october/rain',
            'vendor/barryvdh/laravel-debugbar',
            'vendor/laravel', // This will ignore all Laravel framework queries
        ],
    ],

    'events' => [
        'ignore_framework_events' => true,
        'ignored_events'          => [],
    ],

    'jobs' => [
        'ignored_jobs' => [],
    ],

    'model' => [
        'trackable_events' => [
            'restored',
            'updated',
            'created',
            'deleted',
        ],
    ],

    'http' => [
        'max_body_size'  => 64, // KB, longer request/response bodies are truncated
        'hidden_headers' => [
            'authorization',
            'proxy-authorization',
            'cookie',
            'set-cookie',
            'x-api-key',
            'x-xsrf-token',
            'x-csrf-token',
        ],
    ],

    /*
    | Values of these .env keys are masked in illuminar()->showEnv()
    */
    'hidden_env_keys' => [
        '*PASSWORD*',
        '*SECRET*',
        '*TOKEN*',
        '*_KEY',
        'APP_KEY',
    ],
];
