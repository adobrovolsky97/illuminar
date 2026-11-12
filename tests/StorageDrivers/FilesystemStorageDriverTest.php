<?php

namespace Adobrovolsky97\Illuminar\Tests\StorageDrivers;

use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use Illuminate\Filesystem\Filesystem;

/**
 * Class FilesystemStorageDriverTest
 */
class FilesystemStorageDriverTest extends StorageDriverTestCase
{
    protected function makeDriver(): StorageDriverInterface
    {
        return new FilesystemStorageDriver(new Filesystem());
    }

    public function testCorruptedLinesAreSkipped(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump']);
        file_put_contents($this->directory . '/illuminar.jsonl', "{broken json\n", FILE_APPEND);
        $this->driver->saveEntry(['uuid' => '2', 'type' => 'dump']);

        $this->assertCount(2, $this->driver->getData());
    }

    public function testCompactionKeepsLatestEntries(): void
    {
        config(['illuminar.storage.max_file_size' => 0.01]); // ~10KB

        foreach (range(1, 100) as $i) {
            $this->driver->saveEntry(['uuid' => (string)$i, 'type' => 'dump', 'data' => str_repeat('x', 500)]);
        }

        $this->assertLessThanOrEqual(10 * 1024 * 1.1, filesize($this->directory . '/illuminar.jsonl'));
        $this->assertSame('100', $this->driver->getData()[0]['uuid']);
    }
}
