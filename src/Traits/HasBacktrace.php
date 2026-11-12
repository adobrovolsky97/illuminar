<?php

namespace Adobrovolsky97\Illuminar\Traits;

/**
 * Trait HasBacktrace
 */
trait HasBacktrace
{
    /**
     * Framework paths which should be skipped when searching for the caller
     *
     * @var string[]
     */
    protected array $pathsToRemove = [
        'vendor/adobrovolsky97/illuminar',
        'vendor/laravel/framework/src/Illuminate/Support',
        'vendor/laravel/framework/src/Illuminate/Collections',
        'vendor/laravel/framework/src/Illuminate/Macroable',
        'vendor/laravel/framework/src/Illuminate/Conditionable',
        'vendor/laravel/framework/src/Illuminate/Database',
        'vendor/laravel/framework/src/Illuminate/Events',
        'vendor/laravel/framework/src/Illuminate/Container',
        'vendor/laravel/framework/src/Illuminate/Queue',
        'vendor/laravel/framework/src/Illuminate/Bus',
        'vendor/laravel/framework/src/Illuminate/Foundation',
        'vendor/laravel/framework/src/Illuminate/Cache',
        'vendor/laravel/framework/src/Illuminate/Http',
        'vendor/laravel/framework/src/Illuminate/Log',
    ];

    /**
     * Get caller frame from stack trace.
     *
     * @param array $additionalPaths
     * @return array{file?: string, line?: int}
     */
    protected function getCallerFromStackTrace(array $additionalPaths = []): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60);

        $pathsToRemove = array_map([self::class, 'normalizePath'], array_merge(
            [self::packagePath()],
            $this->pathsToRemove,
            $additionalPaths
        ));

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $file = self::normalizePath($frame['file']);

            foreach ($pathsToRemove as $path) {
                if ($path !== '' && str_contains($file, $path)) {
                    continue 2;
                }
            }

            $view = self::resolveCompiledView($frame['file']);

            return [
                'file' => $view ?? $frame['file'],
                // line numbers of compiled views do not match the original template
                'line' => $view === null ? ($frame['line'] ?? 0) : 0,
            ];
        }

        return [];
    }

    /**
     * Real path of the package sources. Works for vendor installs as well as for
     * symlinked "path" repositories used during local package development.
     */
    private static function packagePath(): string
    {
        static $path;

        return $path ??= (realpath(dirname(__DIR__)) ?: dirname(__DIR__)) . '/';
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Map compiled blade view (storage/framework/views/hash.php) to the original template.
     */
    private static function resolveCompiledView(string $file): ?string
    {
        static $cache = [];

        if (!str_contains(self::normalizePath($file), 'storage/framework/views/')) {
            return null;
        }

        if (array_key_exists($file, $cache)) {
            return $cache[$file];
        }

        $original = null;
        $handle = @fopen($file, 'rb');

        if ($handle !== false) {
            $size = (int)@filesize($file);
            fseek($handle, max(0, $size - 1024));
            $tail = (string)fread($handle, 1024);
            fclose($handle);

            if (preg_match('#/\*\*PATH (.*?) ENDPATH\*\*/#', $tail, $matches)) {
                $original = $matches[1];
            }
        }

        return $cache[$file] = $original;
    }
}
