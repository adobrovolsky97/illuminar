<?php

namespace Adobrovolsky97\Illuminar\Factories;

use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Exception;

/**
 * Class StorageDriverFactory
 */
class StorageDriverFactory
{
    /**
     * Get storage driver for config
     *
     * @return string
     * @throws Exception
     */
    public static function getDriverForConfig(): string
    {
        // TODO support other storages later
        switch (config('illuminar.storage.driver')) {
            case 'file':
                return FilesystemStorageDriver::class;
            default:
                throw new Exception('Unknown storage driver');
        }
    }
}
