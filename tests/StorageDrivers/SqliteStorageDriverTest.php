<?php

namespace Adobrovolsky97\Illuminar\Tests\StorageDrivers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\StorageDrivers\SqliteStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

/**
 * Class SqliteStorageDriverTest
 */
class SqliteStorageDriverTest extends StorageDriverTestCase
{
    protected function makeDriver(): StorageDriverInterface
    {
        return new SqliteStorageDriver(new Filesystem());
    }

    public function testFiltersByTypeAndContextUsingIndexes(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump', 'context_id' => 'a']);
        $this->driver->saveEntry(['uuid' => '2', 'type' => 'query', 'context_id' => 'a']);
        $this->driver->saveEntry(['uuid' => '3', 'type' => 'query', 'context_id' => 'b']);

        $this->assertSame(['3', '2'], array_column($this->driver->getFilteredData(['query']), 'uuid'));
        $this->assertSame(['2', '1'], array_column($this->driver->getFilteredData([], 'a'), 'uuid'));
        $this->assertSame(['dump' => 1, 'query' => 2], $this->driver->countByType());
        $this->assertSame(['query' => 1], $this->driver->countByType('b'));
    }

    public function testOldEntriesArePruned(): void
    {
        config(['illuminar.storage.limit' => 10]);

        foreach (range(1, 120) as $i) {
            $this->driver->saveEntry(['uuid' => (string)$i, 'type' => 'dump']);
        }

        $count = (new \PDO('sqlite:' . $this->directory . '/illuminar.sqlite'))->query('SELECT COUNT(*) FROM entries')->fetchColumn();

        $this->assertLessThanOrEqual(60, (int)$count);
        $this->assertSame('120', $this->driver->getData()[0]['uuid']);
    }

    public function testDriverIsResolvedFromConfigAndDoesNotTouchApplicationDatabase(): void
    {
        StorageDriverFactory::fake(false);

        try {
            config(['illuminar.storage.driver' => 'sqlite']);
            $this->app->forgetInstance(StorageDriverInterface::class);

            $this->assertInstanceOf(SqliteStorageDriver::class, StorageDriverFactory::getDriverForConfig());

            illuminar()->trackQueries();
            DB::select('select 1');

            // Only the application query is recorded, Illuminar's own sqlite queries are invisible
            $this->assertCount(1, StorageDriverFactory::getDriverForConfig()->getData());
        } finally {
            StorageDriverFactory::fake();
        }
    }
}
