<?php

namespace Adobrovolsky97\Illuminar\Tests\StorageDrivers;

use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Mockery;

/**
 * Class FilesystemStorageDriverTest
 */
class FilesystemStorageDriverTest extends TestCase
{
    /**
     * @throws Exception
     * @return void
     */
    public function testSaveDataToStorage(): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isDirectory')->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $filesystem->shouldReceive('put')->twice();

        $driver = new FilesystemStorageDriver($filesystem);
        $driver->save(['data']);
    }


    /**
     * @throws Exception
     * @return void
     */
    public function testAppendDataToStorage(): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isDirectory')->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $filesystem->shouldReceive('exists')->andReturn(true);
        $filesystem->shouldReceive('get')->andReturn(json_encode(['existingData']));
        $filesystem->shouldReceive('put')->twice();

        $driver = new FilesystemStorageDriver($filesystem);
        $driver->append(['data']);
    }

    /**
     * @throws FileNotFoundException
     * @return void
     */
    public function testGetDataFromStorage(): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('exists')->andReturn(true);
        $filesystem->shouldReceive('get')->andReturn(json_encode(['data']));

        $driver = new FilesystemStorageDriver($filesystem);
        $this->assertEquals(['data'], $driver->getData());
    }

    /**
     * @throws FileNotFoundException
     * @return void
     */
    public function testGetDataFromNonExistentStorage(): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('exists')->andReturn(false);

        $driver = new FilesystemStorageDriver($filesystem);
        $this->assertEquals([], $driver->getData());
    }


    /**
     * @throws Exception
     * @return void
     */
    public function testCreateDirectoryIfNotExists(): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isDirectory')->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $filesystem->shouldReceive('put')->twice();

        $driver = new FilesystemStorageDriver($filesystem);
        $driver->save(['data']);
    }
}
