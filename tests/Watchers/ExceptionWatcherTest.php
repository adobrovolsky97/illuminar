<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ExceptionWatcherTest
 */
class ExceptionWatcherTest extends TestCase
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testExceptionsTracking()
    {
        illuminar()->trackExceptions();

        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new Exception('Something went wrong.');
        $handler->report($exception);

        illuminar()->stopTrackingExceptions();
        $handler->report($exception);

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ExceptionWatcher::getName(), $data[0]['type']);
    }
}
