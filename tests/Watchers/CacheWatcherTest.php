<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;

/**
 * Class CacheWatcherTest
 */
class CacheWatcherTest extends TestCase
{
    /**
     * Testing that all possible cache events are tracked
     *
     * @return void
     */
    public function testEvents(): void
    {
        $events = [
            new CacheHit('test', 'value'),
            new CacheMissed('test'),
            new KeyWritten('test', 'value'),
            new KeyForgotten('test'),
        ];

        foreach ($events as $event) {

            illuminar()->trackCaches();
            Event::dispatch($event); // this one should be tracked

            illuminar()->stopTrackingCaches();
            Event::dispatch($event); // this one should not be tracked

            $data = StorageDriverFactory::getDriverForConfig()->getData();

            $this->assertNotEmpty($data);
            $this->assertEquals(CacheWatcher::getName(), $data[0]['type']);
        }
    }
}
