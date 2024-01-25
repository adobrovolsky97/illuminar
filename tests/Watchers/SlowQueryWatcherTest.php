<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Events\SlowQueryFound;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestUserModel;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class SlowQueryWatcherTest
 */
class SlowQueryWatcherTest extends QueryWatcherTest
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testSlowQueriesDetected()
    {
        Event::fake();

        $config = $this->app->get('config');
        $config->set('illuminar.queries.slow_time_ms', 0.1);

        illuminar()->trackSlowQueries();

        $data = Collection::times(300, function () {
            return [
                'name'  => Str::random(),
                'email' => Str::random() . '@illuminar.com',
            ];
        });

        TestUserModel::insert($data->toArray());

        Event::assertDispatched(SlowQueryFound::class);

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(QueryWatcher::getName(), $entry['type']);
        $this->assertTrue($entry['is_slow']);
    }
}
