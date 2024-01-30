<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestEvent;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

/**
 * Class EventWatcherTest
 */
class EventWatcherTest extends TestCase
{
    /**
     * @return void
     */
    public function testTrackingEvents()
    {
        illuminar()->trackEvents();
        Event::dispatch(new TestEvent(['key' => 'value']));

        illuminar()->stopTrackingEvents();
        Event::dispatch(new TestEvent(['key' => 'value']));

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(EventWatcher::getName(), $data[0]['type']);
    }

    /**
     * Testing config
     * illuminar.events.ignore_framework_events
     *
     * @return void
     */
    public function testIgnorePackageEvents(): void
    {
        Config::set('illuminar.events.ignore_framework_events', false);

        illuminar()->trackEvents();

        Event::dispatch(new TestEvent(['key' => 'value']));
        Event::dispatch('eloquent.bootstrapping: *');

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertCount(2, $data);
        $this->assertEquals(TestEvent::class, $data[0]['event_name']);
        $this->assertEquals('eloquent.bootstrapping: *', $data[1]['event_name']);

        Config::set('illuminar.events.ignore_framework_events', true);
        Event::dispatch('eloquent.bootstrapping: *');

        $this->assertCount(2, StorageDriverFactory::getDriverForConfig()->getData());
    }

    /**
     * Testing config illuminar.events.ignored_events
     *
     * @return void
     */
    public function testIgnoreCustomEvents(): void
    {
        Config::set('illuminar.events.ignored_events', [TestEvent::class]);
        illuminar()->trackEvents();

        Event::dispatch(new TestEvent(['key' => 'value']));

        $this->assertEmpty(StorageDriverFactory::getDriverForConfig()->getData());
    }
}
