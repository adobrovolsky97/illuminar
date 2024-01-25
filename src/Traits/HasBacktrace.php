<?php

namespace Adobrovolsky97\Illuminar\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasBacktrace
 */
trait HasBacktrace
{
    /**
     * Paths which should be removed from trace
     *
     * @var array|string[]
     */
    protected array $pathsToRemove = [
        'vendor/adobrovolsky97/illuminar',
        'vendor/laravel/framework/src/Illuminate/Support',
        'vendor/laravel/framework/src/Illuminate/Database',
        'vendor/laravel/framework/src/Illuminate/Events',
        'vendor/laravel/framework/src/Illuminate/Container',
        'vendor/laravel/framework/src/Illuminate/Queue',
        'vendor/laravel/framework/src/Illuminate/Foundation',
        'vendor/laravel/framework/src/Illuminate/Cache',
        'vendor/laravel/framework/src/Illuminate/Http',
    ];

    /**
     * Get caller from stack trace.
     *
     * @param array $additionalPaths
     * @return array
     */
    protected function getCallerFromStackTrace(array $additionalPaths = []): array
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 50));

        $pathsToRemove = array_merge($this->pathsToRemove, $additionalPaths);

        $caller = collect($trace)->first(function ($frame) use ($pathsToRemove) {
            return isset($frame['file']) && !Str::contains($frame['file'], $pathsToRemove);
        });

        return $caller ?? [];
    }
}
