<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestEvent;
use Adobrovolsky97\Illuminar\Tests\TestCase;
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
        $this->assertEmpty(DataCollector::getBatch());

        illuminar()->trackEvents();
        Event::dispatch(new TestEvent(['key' => 'value']));

        illuminar()->stopTrackingEvents();
        Event::dispatch(new TestEvent(['key' => 'value']));

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals('event', $entry['type']);
    }

    /**
     * Testing config
     * illuminar.events.ignore_framework_events
     *
     * @return void
     */
    public function testIgnorePackageEvents(): void
    {
        $this->assertEmpty(DataCollector::getBatch());

        Config::set('illuminar.events.ignore_framework_events', false);

        illuminar()->trackEvents();

        Event::dispatch(new TestEvent(['key' => 'value']));
        Event::dispatch('eloquent.bootstrapping: *');

        $batch = array_values(DataCollector::getBatch());

        $this->assertCount(2, $batch);
        $this->assertEquals(TestEvent::class, $batch[0]['event_name']);
        $this->assertEquals('eloquent.bootstrapping: *', $batch[1]['event_name']);

        Config::set('illuminar.events.ignore_framework_events', true);
        Event::dispatch('eloquent.bootstrapping: *');

        $this->assertCount(2, DataCollector::getBatch());
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

        $this->assertEmpty(DataCollector::getBatch());
    }
}
