<?php

namespace Adobrovolsky97\Illuminar\Factories;

use Adobrovolsky97\Illuminar\StorageDrivers\FakeStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;

/**
 * Class StorageDriverFactory
 */
class StorageDriverFactory
{
    /**
     * @var bool
     */
    private static bool $fake = false;

    /**
     * Get storage driver for config
     *
     * @return StorageDriverInterface
     */
    public static function getDriverForConfig(): StorageDriverInterface
    {
        if (self::$fake) {
            return app(FakeStorageDriver::class);
        }

        // TODO support other storages later
        switch (config('illuminar.storage.driver')) {
            case 'file':
            default:
                return app(FilesystemStorageDriver::class);
        }
    }

    /**
     * @return void
     */
    public static function fake()
    {
        self::$fake = true;
    }
}
