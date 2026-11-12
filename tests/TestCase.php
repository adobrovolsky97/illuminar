<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Support\Context;

/**
 * Class TestCase
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        Illuminar::$throwInternalErrors = true;
        Context::reset();
        StorageDriverFactory::fake();

        parent::setUp();

        StorageDriverFactory::getDriverForConfig()->clear();
    }

    protected function tearDown(): void
    {
        StorageDriverFactory::getDriverForConfig()->clear();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('illuminar.enabled', true);
        $config->set('illuminar.track', array_fill_keys(array_keys(Illuminar::TRACKABLE_WATCHERS), false));

        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            'Adobrovolsky97\Illuminar\ServiceProvider',
        ];
    }

    /**
     * Cache events got the store name as the first constructor argument in Laravel 11
     */
    protected function cacheEvent(string $class, ...$args): object
    {
        $parameters = (new \ReflectionClass($class))->getConstructor()->getParameters();

        if ($parameters[0]->getName() === 'storeName') {
            array_unshift($args, 'array');
        }

        return new $class(...$args);
    }

    protected function entries(): array
    {
        return StorageDriverFactory::getDriverForConfig()->getData();
    }
}
