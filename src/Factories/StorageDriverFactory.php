<?php

namespace Adobrovolsky97\Illuminar\Factories;

use Adobrovolsky97\Illuminar\StorageDrivers\FakeStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\FilesystemStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\SqliteStorageDriver;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use InvalidArgumentException;

/**
 * Class StorageDriverFactory
 */
class StorageDriverFactory
{
    private static bool $fake = false;

    public static function getDriverForConfig(): StorageDriverInterface
    {
        if (self::$fake) {
            return app(FakeStorageDriver::class);
        }

        return app(StorageDriverInterface::class);
    }

    /**
     * Build driver configured in illuminar.storage.driver
     */
    public static function make(): StorageDriverInterface
    {
        $driver = config('illuminar.storage.driver', 'sqlite');

        if ($driver === 'sqlite') {
            return SqliteStorageDriver::isSupported()
                ? app(SqliteStorageDriver::class)
                : app(FilesystemStorageDriver::class);
        }

        if ($driver === 'file' || empty($driver)) {
            return app(FilesystemStorageDriver::class);
        }

        if (is_string($driver) && class_exists($driver) && is_subclass_of($driver, StorageDriverInterface::class)) {
            return app($driver);
        }

        throw new InvalidArgumentException("Illuminar storage driver [$driver] is not supported.");
    }

    public static function fake(bool $fake = true): void
    {
        self::$fake = $fake;
    }
}
