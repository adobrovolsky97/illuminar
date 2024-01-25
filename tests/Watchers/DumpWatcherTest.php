<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Exception;

/**
 * Class DumpWatcherTest
 */
class DumpWatcherTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testDump(): void
    {
        illuminar()->dump('test');

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals('dump', $entry->toArray()['type']);
    }
}
