<?php

namespace Adobrovolsky97\Illuminar;

use Adobrovolsky97\Illuminar\Payloads\QueryMacroPayload;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Blade;

/**
 * Class ServiceProvider
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerPublishes();

        $this->mergeConfigFrom(__DIR__ . '/config/illuminar.php', 'illuminar');
        $this->loadRoutesFrom(__DIR__ . '/routes/illuminar.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'illuminar');

        $this->registerBladeDirectives();
        $this->registerMacros();

        $this->app->singleton(Illuminar::class);

        if (!config('illuminar.enabled')) {
            return;
        }

        Illuminar::initialize();
    }

    /**
     * @return void
     */
    private function registerPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/illuminar'),
        ]);

        $this->publishes([
            __DIR__ . '/config/illuminar.php' => config_path('illuminar.php'),
        ]);
    }

    /**
     * @return void
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('illuminar', function ($expression) {
            return "<?php app('Adobrovolsky97\Illuminar\Illuminar')->dump($expression); ?>";
        });
    }

    /**
     * @return void
     */
    private function registerMacros(): void
    {
        EloquentBuilder::macro('illuminar', function () {
            DataCollector::addToBatch(new QueryMacroPayload($this));
            return $this;
        });

        QueryBuilder::macro('illuminar', function () {
            DataCollector::addToBatch(new QueryMacroPayload($this));
            return $this;
        });
    }
}
