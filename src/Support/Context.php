<?php

namespace Adobrovolsky97\Illuminar\Support;

use Illuminate\Support\Str;
use Throwable;

/**
 * Execution context (HTTP request, artisan command or queued job) an entry belongs to.
 * Allows to see all queries / dumps / events produced by a single request.
 */
class Context
{
    private static ?array $current = null;

    private static array $stack = [];

    /**
     * @return array{id: string, label: string}
     */
    public static function current(): array
    {
        return self::$current ??= self::resolve();
    }

    /**
     * Enter a nested context (e.g. a job processed by a worker)
     */
    public static function push(string $label): void
    {
        self::$stack[] = self::$current;
        self::$current = ['id' => (string)Str::uuid(), 'label' => $label];
    }

    /**
     * Leave nested context
     */
    public static function pop(): void
    {
        self::$current = array_pop(self::$stack);
    }

    /**
     * Forget current context (new request in Octane, new test etc.)
     */
    public static function reset(): void
    {
        self::$current = null;
        self::$stack = [];
    }

    private static function resolve(): array
    {
        return ['id' => (string)Str::uuid(), 'label' => self::label()];
    }

    private static function label(): string
    {
        try {
            if (app()->runningInConsole()) {
                $argv = $_SERVER['argv'] ?? [];
                $script = basename((string)array_shift($argv));

                return trim($script . ' ' . implode(' ', $argv)) ?: 'console';
            }

            $request = app('request');

            return $request->method() . ' /' . ltrim($request->path(), '/');
        } catch (Throwable $e) {
            return 'unknown';
        }
    }
}
