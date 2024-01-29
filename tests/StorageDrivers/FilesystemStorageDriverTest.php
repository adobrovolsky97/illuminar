<?php

namespace Adobrovolsky97\Illuminar\Tests\StorageDrivers;

use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\Exception;

/**
 * Class FilesystemStorageDriverTest
 */
class FilesystemStorageDriverTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FilesystemStorageDriver
     */
    private FilesystemStorageDriver $storageDriver;

    /**
     * @throws Exception
     */
    public function setUp(): void
   {
       parent::setUp();

       $this->filesystem = $this->createMock(Filesystem::class);
       $this->storageDriver = new FilesystemStorageDriver($this->filesystem);
   }

    /**
     * New uuid entry should append data
     *
     * @return void
     */
    public function testSaveEntityWithNewUuidShouldAppendData(): void
    {
        $this->filesystem->method('exists')->willReturn(true);
        $this->filesystem->method('get')->willReturn(json_encode([['uuid' => 'existing-uuid', 'key' => 'value']]));
        $this->filesystem->method('isDirectory')->willReturn(true);

        $this->filesystem->expects($this->once())->method('put');

        $this->storageDriver->saveEntry(['uuid' => 'new-uuid', 'key' => 'value']);
    }

    /**
     * Existing uuid should update data
     *
     * @return void
     */
    public function testSaveEntryUpdatesDataWhenUuidFound(): void
    {
        $this->filesystem->method('exists')->willReturn(true);
        $this->filesystem->method('get')->willReturn(json_encode([['uuid' => 'existing-uuid', 'data' => 'existing-data']]));
        $this->filesystem->method('isDirectory')->willReturn(true);

        $this->filesystem->expects($this->once())->method('put');

        $this->storageDriver->saveEntry(['uuid' => 'existing-uuid', 'data' => 'updated-data']);
    }

    /**
     * Empty array should be returned when file does not exist
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testGetDataReturnsEmptyArrayWhenFileDoesNotExist(): void
    {
        $this->filesystem->method('exists')->willReturn(false);

        $data = $this->storageDriver->getData();

        $this->assertEquals([], $data);
    }

    /**
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testGetDataReturnsDecodedJsonWhenFileExists(): void
    {
        $this->filesystem->method('exists')->willReturn(true);
        $this->filesystem->method('get')->willReturn(json_encode([['uuid' => 'existing-uuid', 'data' => 'existing-data']]));
        $this->filesystem->method('isDirectory')->willReturn(true);

        $data = $this->storageDriver->getData();

        $this->assertEquals([['uuid' => 'existing-uuid', 'data' => 'existing-data']], $data);
    }

    /**
     * @return void
     */
    public function testClearDeletesFileWhenItExists(): void
    {
        $this->filesystem->method('exists')->willReturn(true);
        $this->filesystem->expects($this->once())->method('delete');
        $this->filesystem->method('isDirectory')->willReturn(true);

        $this->storageDriver->clear();
    }

    /**
     * @return void
     */
    public function testClearDoesNothingWhenFileDoesNotExist(): void
    {
        $this->filesystem->method('exists')->willReturn(false);
        $this->filesystem->expects($this->never())->method('delete');

        $this->storageDriver->clear();
    }
}
