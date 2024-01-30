<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
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

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(DumpWatcher::getName(), $data[0]['type']);
    }
}
