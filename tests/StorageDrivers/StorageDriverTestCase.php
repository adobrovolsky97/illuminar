<?php

namespace Adobrovolsky97\Illuminar\Tests\StorageDrivers;

use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

/**
 * Contract tests for every storage driver
 */
abstract class StorageDriverTestCase extends TestCase
{
    protected string $directory;

    protected StorageDriverInterface $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/illuminar-tests-' . uniqid();

        config([
            'illuminar.storage.path'     => $this->directory,
            'illuminar.storage.filename' => 'illuminar',
            'illuminar.storage.limit'    => 2000,
        ]);

        $this->driver = $this->makeDriver();
    }

    abstract protected function makeDriver(): StorageDriverInterface;

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function testReturnsNewestEntriesFirst(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump']);
        $this->driver->saveEntry(['uuid' => '2', 'type' => 'dump']);

        $this->assertSame(['2', '1'], array_column($this->driver->getData(), 'uuid'));
    }

    public function testPartialUpdatesAreMerged(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump', 'data' => [1]]);
        $this->driver->saveEntry(['uuid' => '2', 'type' => 'dump']);
        $this->driver->saveEntry(['uuid' => '1', 'tag' => 'tag']);

        $data = $this->driver->getData();

        $this->assertCount(2, $data);
        $this->assertSame(['uuid' => '1', 'type' => 'dump', 'data' => [1], 'tag' => 'tag'], $data[1]);
    }

    public function testOrphanPartialUpdatesAreIgnored(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'status' => 'processed']);

        $this->assertSame([], $this->driver->getData());
    }

    public function testEntryWithoutUuidIsIgnored(): void
    {
        $this->driver->saveEntry(['type' => 'dump']);

        $this->assertSame([], $this->driver->getData());
    }

    public function testInvalidUtf8DoesNotWipeStorage(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump']);
        $this->driver->saveEntry(['uuid' => '2', 'type' => 'query', 'sql' => "select \xB1\x31"]);

        $this->assertCount(2, $this->driver->getData());
    }

    public function testLimit(): void
    {
        config(['illuminar.storage.limit' => 3]);

        foreach (range(1, 5) as $i) {
            $this->driver->saveEntry(['uuid' => (string)$i, 'type' => 'dump']);
        }

        $this->assertSame(['5', '4', '3'], array_column($this->driver->getData(), 'uuid'));
    }

    public function testClear(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump']);
        $this->driver->clear();

        $this->assertSame([], $this->driver->getData());
    }

    public function testGetDataWhenFileDoesNotExist(): void
    {
        $this->assertSame([], $this->driver->getData());
    }
    public function testCreatesDirectoryWithGitignore(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump']);

        $this->assertFileExists($this->directory . '/.gitignore');
    }

    public function testUnicodeIsPreserved(): void
    {
        $this->driver->saveEntry(['uuid' => '1', 'type' => 'dump', 'data' => 'Привет 👋']);

        $this->assertSame('Привет 👋', $this->driver->getData()[0]['data']);
    }
}
