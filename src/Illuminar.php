<?php

namespace Adobrovolsky97\Illuminar;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Payloads\DumpPayload;
use Adobrovolsky97\Illuminar\Payloads\MailablePreviewPayload;
use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Adobrovolsky97\Illuminar\Watchers\SlowQueryWatcher;
use Adobrovolsky97\Illuminar\Watchers\Watcher;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class Illuminar
 *
 * @method void trackQueries()
 * @method void stopTrackingQueries()
 * @method void trackSlowQueries()
 * @method void stopTrackingSlowQueries()
 * @method void trackExceptions()
 * @method void stopTrackingExceptions()
 * @method void trackModels()
 * @method void stopTrackingModels()
 * @method void trackJobs()
 * @method void stopTrackingJobs()
 * @method void trackEvents()
 * @method void stopTrackingEvents()
 * @method void trackCaches()
 * @method void stopTrackingCaches()
 * @method void trackMails()
 * @method void stopTrackingMails()
 * @method void trackHttpRequests()
 * @method void stopTrackingHttpRequests()
 */
final class Illuminar
{
    /**
     * Registered watchers
     *
     * @var array
     */
    private static array $watchers = [];

    /**
     * Trackable watchers which can be enabled/disabled, e.g. trackQueries, stopTrackingQueries
     *
     * @var array
     */
    private array $trackableWatchers = [
        'query'        => QueryWatcher::class,
        'slow_query'   => SlowQueryWatcher::class,
        'exception'    => ExceptionWatcher::class,
        'model'        => ModelWatcher::class,
        'job'          => JobWatcher::class,
        'event'        => EventWatcher::class,
        'cache'        => CacheWatcher::class,
        'mail'         => MailWatcher::class,
        'http_request' => HttpRequestWatcher::class
    ];

    /**
     * Initialize Illuminar
     *
     * @return void
     */
    public static function initialize(): void
    {
        if (!config('illuminar.enabled')) {
            return;
        }

        self::resetWatchers();

        self::registerWatcher(app(DumpWatcher::class));
        // This one should be registered by default as it will catch JobProcessed and JobFailed events
        self::registerWatcher(app(JobWatcher::class));

        app()->terminating(function () {
            DataCollector::storeData();
        });
    }

    /**
     * Track/stop tracking logic
     *
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        foreach ($this->trackableWatchers as $type => $watcher) {
            if ($name === 'track' . ucfirst(Str::plural(Str::camel($type)))) {
                $this->enableWatcher($type);
                return;
            }

            if ($name === 'stopTracking' . ucfirst(Str::plural(Str::camel($type)))) {
                $this->disableWatcher($type);
                return;
            }
        }

        throw new Exception('Method ' . $name . ' does not exist');
    }

    /**
     * Enable all watchers
     *
     * @return void
     */
    public function trackAll(): void
    {
        foreach ($this->trackableWatchers as $type => $watcher) {
            $this->enableWatcher($type);
        }
    }

    /**
     * Disable all watchers
     *
     * @return void
     */
    public function stopTrackingAll(): void
    {
        foreach ($this->trackableWatchers as $type => $watcher) {
            $this->disableWatcher($type);
        }
    }

    /**
     * Debug mailable
     *
     * @param Mailable $mailable
     * @return Illuminar
     */
    public function mailable(Mailable $mailable): self
    {
        DataCollector::addToBatch(new MailablePreviewPayload($mailable));

        return $this;
    }

    /**
     * Create dump
     *
     * @param ...$args
     * @return DumpPayload
     */
    public function dump(...$args): DumpPayload
    {
        /** @var DumpWatcher $watcher */
        $watcher = self::getWatcher(DumpWatcher::getName());

        if (is_null($watcher)) {
            $watcher = app(DumpWatcher::class);
            self::registerWatcher($watcher);
        }

        return $watcher->addDump(...$args);
    }

    /**
     * Show .env
     *
     * @return void
     * @throws Exception
     */
    public function showEnv(): void
    {
        if (!file_exists(base_path('.env'))) {
            throw new Exception('File .env does not exist');
        }

        $envFileContent = file_get_contents(base_path('.env'));

        $lines = explode("\n", $envFileContent);

        $envArray = [];

        foreach ($lines as $line) {
            // Ignore comments and empty lines
            if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $envArray[trim($key)] = trim($value);
        }

        $this->dump($envArray)->tag('env');
    }

    /**
     * Get data
     *
     * @return array
     */
    public static function getData(): array
    {
        try {
            return app(StorageDriverFactory::getDriverForConfig())->getData();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get watcher from loaded watchers
     *
     * @param string $name
     * @return Watcher|null
     */
    private static function getWatcher(string $name): ?Watcher
    {
        return self::$watchers[$name] ?? null;
    }

    /**
     * Register watcher
     *
     * @param Watcher $watcher
     * @return void
     */
    private static function registerWatcher(Watcher $watcher): void
    {
        self::$watchers[$watcher::getName()] = $watcher;
    }

    /**
     * Enable specific watcher
     *
     * @param string $type
     * @return void
     */
    private function enableWatcher(string $type): void
    {
        $watcher = self::getWatcher($type);

        if (is_null($watcher)) {
            self::registerWatcher(app($this->trackableWatchers[$type])->enable());
            return;
        }

        $watcher->enable();
    }

    /**
     * Disable specific watcher
     *
     * @param string $type
     * @return void
     */
    private function disableWatcher(string $type): void
    {
        optional(self::getWatcher($type))->disable();
    }

    /**
     * Reset watchers
     *
     * @return void
     */
    private static function resetWatchers(): void
    {
        self::$watchers = [];
    }
}
