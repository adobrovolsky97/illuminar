<?php

namespace Adobrovolsky97\Illuminar;

use Adobrovolsky97\Illuminar\Console\ClearCommand;
use Adobrovolsky97\Illuminar\Console\McpCommand;
use Adobrovolsky97\Illuminar\Console\TailCommand;
use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Payloads\QueryMacroPayload;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use Adobrovolsky97\Illuminar\Support\Context;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;

/**
 * Class ServiceProvider
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/illuminar.php', 'illuminar');

        $this->app->singleton(Illuminar::class);
        $this->app->singleton(StorageDriverInterface::class, function () {
            return StorageDriverFactory::make();
        });
    }

    public function boot(): void
    {
        $this->registerPublishes();
        $this->registerCommands();

        $this->loadRoutesFrom(__DIR__ . '/routes/illuminar.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'illuminar');

        $this->registerBladeDirectives();
        $this->registerMacros();
        $this->registerOctaneListeners();

        Illuminar::initialize();
    }

    private function registerPublishes(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/config/illuminar.php' => config_path('illuminar.php'),
        ], ['illuminar', 'illuminar-config']);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ClearCommand::class, TailCommand::class, McpCommand::class]);
        }
    }

    private function registerBladeDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function () {
            Blade::directive('illuminar', function ($expression) {
                return "<?php app(\\Adobrovolsky97\\Illuminar\\Illuminar::class)->dump($expression); ?>";
            });
        });
    }

    /**
     * ->illuminar() on query builders, relations and collections
     */
    private function registerMacros(): void
    {
        $queryMacro = function () {
            /** @var EloquentBuilder|QueryBuilder|Relation $this */
            $builder = $this;

            Illuminar::record(function () use ($builder) {
                StorageDriverFactory::getDriverForConfig()->saveEntry((new QueryMacroPayload($builder))->toArray());
            });

            return $this;
        };

        EloquentBuilder::macro('illuminar', $queryMacro);
        QueryBuilder::macro('illuminar', $queryMacro);
        Relation::macro('illuminar', $queryMacro);

        if (!Collection::hasMacro('illuminar')) {
            Collection::macro('illuminar', function (...$args) {
                /** @var Collection $this */
                app(Illuminar::class)->dump($this, ...$args);

                return $this;
            });
        }
    }

    /**
     * Octane keeps the application in memory: reset request context and tracking between requests
     */
    private function registerOctaneListeners(): void
    {
        Event::listen('Laravel\Octane\Events\RequestReceived', function () {
            Context::reset();
            Illuminar::resetTracking();
        });
    }
}
