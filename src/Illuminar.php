<?php

namespace Adobrovolsky97\Illuminar;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Support\PendingDump;
use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Adobrovolsky97\Illuminar\Watchers\LogWatcher;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Adobrovolsky97\Illuminar\Watchers\RequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\SlowQueryWatcher;
use Adobrovolsky97\Illuminar\Watchers\Watcher;
use BadMethodCallException;
use Dotenv\Dotenv;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Class Illuminar
 */
final class Illuminar
{
    /**
     * Trackable watchers which can be enabled/disabled, e.g. trackQueries, stopTrackingQueries
     */
    public const TRACKABLE_WATCHERS = [
        'query'        => QueryWatcher::class,
        'slow_query'   => SlowQueryWatcher::class,
        'exception'    => ExceptionWatcher::class,
        'model'        => ModelWatcher::class,
        'job'          => JobWatcher::class,
        'event'        => EventWatcher::class,
        'cache'        => CacheWatcher::class,
        'mail'         => MailWatcher::class,
        'http_request' => HttpRequestWatcher::class,
        'log'          => LogWatcher::class,
        'request'      => RequestWatcher::class,
    ];

    /**
     * Registered watchers
     *
     * @var Watcher[]
     */
    private static array $watchers = [];

    /**
     * Re-entrance guard: Illuminar's own work (dumping a model, rendering a mail etc.)
     * must not be recorded by other watchers.
     */
    private static bool $recording = false;

    /**
     * Rethrow internal errors instead of swallowing them (used in package tests)
     */
    public static bool $throwInternalErrors = false;

    /**
     * Initialize Illuminar
     */
    public static function initialize(): void
    {
        self::resetWatchers();

        if (!self::isEnabled()) {
            return;
        }

        self::registerWatcher(app(DumpWatcher::class));
        // Registered by default as it catches JobProcessing / JobProcessed / JobFailed events from workers
        self::registerWatcher(app(JobWatcher::class));

        foreach (self::configuredWatchers() as $type) {
            self::enableWatcher($type);
        }
    }

    /**
     * Restore tracking state from the config without registering listeners again.
     * Used between requests in long-running processes (Octane).
     */
    public static function resetTracking(): void
    {
        if (empty(self::$watchers)) {
            self::initialize();

            return;
        }

        $configured = self::configuredWatchers();

        foreach (self::TRACKABLE_WATCHERS as $type => $class) {
            if (in_array($type, $configured, true)) {
                self::enableWatcher($type);
            } else {
                optional(self::getWatcher($type))->disable();
            }
        }
    }

    /**
     * Watcher types enabled in illuminar.track config
     *
     * @return string[]
     */
    public static function configuredWatchers(): array
    {
        $types = [];

        foreach ((array)config('illuminar.track', []) as $type => $enabled) {
            if (filter_var($enabled, FILTER_VALIDATE_BOOLEAN) && isset(self::TRACKABLE_WATCHERS[$type])) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public static function isEnabled(): bool
    {
        return (bool)config('illuminar.enabled');
    }

    /**
     * Run Illuminar's internal logic. Never breaks the application and never records itself.
     */
    public static function record(callable $callback): void
    {
        if (self::$recording || !self::isEnabled()) {
            return;
        }

        self::$recording = true;

        try {
            $callback();
        } catch (Throwable $exception) {
            if (self::$throwInternalErrors) {
                throw $exception;
            }
        } finally {
            self::$recording = false;
        }
    }

    public static function isRecording(): bool
    {
        return self::$recording;
    }

    public function trackQueries(): self
    {
        return $this->track('query');
    }

    public function stopTrackingQueries(): self
    {
        return $this->stopTracking('query');
    }

    public function trackSlowQueries(): self
    {
        return $this->track('slow_query');
    }

    public function stopTrackingSlowQueries(): self
    {
        return $this->stopTracking('slow_query');
    }

    public function trackExceptions(): self
    {
        return $this->track('exception');
    }

    public function stopTrackingExceptions(): self
    {
        return $this->stopTracking('exception');
    }

    public function trackModels(): self
    {
        return $this->track('model');
    }

    public function stopTrackingModels(): self
    {
        return $this->stopTracking('model');
    }

    /**
     * Alias of trackModels()
     */
    public function trackModelEvents(): self
    {
        return $this->trackModels();
    }

    /**
     * Alias of stopTrackingModels()
     */
    public function stopTrackingModelEvents(): self
    {
        return $this->stopTrackingModels();
    }

    public function trackJobs(): self
    {
        return $this->track('job');
    }

    public function stopTrackingJobs(): self
    {
        return $this->stopTracking('job');
    }

    public function trackEvents(): self
    {
        return $this->track('event');
    }

    public function stopTrackingEvents(): self
    {
        return $this->stopTracking('event');
    }

    public function trackCaches(): self
    {
        return $this->track('cache');
    }

    public function stopTrackingCaches(): self
    {
        return $this->stopTracking('cache');
    }

    /**
     * Alias of trackCaches()
     */
    public function trackCache(): self
    {
        return $this->trackCaches();
    }

    /**
     * Alias of stopTrackingCaches()
     */
    public function stopTrackingCache(): self
    {
        return $this->stopTrackingCaches();
    }

    public function trackMails(): self
    {
        return $this->track('mail');
    }

    public function stopTrackingMails(): self
    {
        return $this->stopTracking('mail');
    }

    public function trackHttpRequests(): self
    {
        return $this->track('http_request');
    }

    public function stopTrackingHttpRequests(): self
    {
        return $this->stopTracking('http_request');
    }

    public function trackLogs(): self
    {
        return $this->track('log');
    }

    public function stopTrackingLogs(): self
    {
        return $this->stopTracking('log');
    }

    /**
     * Summary (duration, memory, queries, N+1...) of every request / command / job
     */
    public function trackRequests(): self
    {
        return $this->track('request');
    }

    public function stopTrackingRequests(): self
    {
        return $this->stopTracking('request');
    }

    /**
     * Enable all watchers
     */
    public function trackAll(): self
    {
        foreach (array_keys(self::TRACKABLE_WATCHERS) as $type) {
            $this->track($type);
        }

        return $this;
    }

    /**
     * Disable all watchers
     */
    public function stopTrackingAll(): self
    {
        foreach (array_keys(self::TRACKABLE_WATCHERS) as $type) {
            $this->stopTracking($type);
        }

        return $this;
    }

    /**
     * Track everything only while the callback is running
     *
     * illuminar()->watch(fn () => $service->handle(), ['query', 'model']);
     *
     * @param callable $callback
     * @param string[]|null $types Watcher types, all when null
     * @return mixed Callback result
     */
    public function watch(callable $callback, ?array $types = null)
    {
        $types ??= array_keys(self::TRACKABLE_WATCHERS);

        $previouslyEnabled = array_filter($types, function (string $type) {
            return optional(self::getWatcher($type))->enabled;
        });

        foreach ($types as $type) {
            $this->track($type);
        }

        try {
            return $callback();
        } finally {
            foreach (array_diff($types, $previouslyEnabled) as $type) {
                $this->stopTracking($type);
            }
        }
    }

    /**
     * Generic track / stop tracking methods for the backward compatibility, e.g. trackHttpRequests()
     *
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        foreach (array_keys(self::TRACKABLE_WATCHERS) as $type) {
            $method = ucfirst(Str::plural(Str::camel($type)));

            if ($name === 'track' . $method) {
                return $this->track($type);
            }

            if ($name === 'stopTracking' . $method) {
                return $this->stopTracking($type);
            }
        }

        throw new BadMethodCallException('Method ' . self::class . '::' . $name . ' does not exist.');
    }

    /**
     * Preview mailable / notification mail message without sending it
     *
     * @param Renderable $mailable Mailable or MailMessage
     */
    public function mailable(Renderable $mailable): self
    {
        if (!self::isEnabled()) {
            return $this;
        }

        /** @var MailWatcher $watcher */
        $watcher = self::getWatcher(MailWatcher::getName()) ?? self::registerWatcher(app(MailWatcher::class));

        $watcher->mailable($mailable);

        return $this;
    }

    /**
     * Create dump
     *
     * @param mixed ...$args
     */
    public function dump(...$args): PendingDump
    {
        if (!self::isEnabled()) {
            return new PendingDump(null);
        }

        /** @var DumpWatcher $watcher */
        $watcher = self::getWatcher(DumpWatcher::getName()) ?? self::registerWatcher(app(DumpWatcher::class));

        return $watcher->addDump(...$args);
    }

    /**
     * Dump and die
     *
     * @param mixed ...$args
     * @return never
     */
    public function dd(...$args): void
    {
        $this->dump(...$args)->die();
    }

    /**
     * Show .env variables. Sensitive values (see illuminar.hidden_env_keys) are masked.
     *
     * @throws RuntimeException
     */
    public function showEnv(bool $maskSensitive = true): PendingDump
    {
        if (!self::isEnabled()) {
            return new PendingDump(null);
        }

        $path = app()->environmentFilePath();

        if (!is_file($path)) {
            throw new RuntimeException("File $path does not exist");
        }

        $variables = Dotenv::parse((string)file_get_contents($path));

        if ($maskSensitive) {
            $patterns = (array)config('illuminar.hidden_env_keys', []);

            foreach ($variables as $key => $value) {
                if ($value !== null && $value !== '' && Str::is($patterns, $key)) {
                    $variables[$key] = '********';
                }
            }
        }

        return $this->dump($variables)->tag('env');
    }

    /**
     * Remove all stored entries
     */
    public function clear(): self
    {
        StorageDriverFactory::getDriverForConfig()->clear();

        return $this;
    }

    /**
     * Get watcher from loaded watchers
     */
    public static function getWatcher(string $name): ?Watcher
    {
        return self::$watchers[$name] ?? null;
    }

    private function track(string $type): self
    {
        if (self::isEnabled()) {
            self::enableWatcher($type);
        }

        return $this;
    }

    private function stopTracking(string $type): self
    {
        optional(self::getWatcher($type))->disable();

        return $this;
    }

    private static function registerWatcher(Watcher $watcher): Watcher
    {
        return self::$watchers[$watcher::getName()] = $watcher;
    }

    private static function enableWatcher(string $type): void
    {
        $watcher = self::getWatcher($type) ?? self::registerWatcher(app(self::TRACKABLE_WATCHERS[$type]));

        $watcher->enable();
    }

    private static function resetWatchers(): void
    {
        self::$watchers = [];
    }
}
