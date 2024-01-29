<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

/**
 * Class FakeStorageDriver
 */
class FakeStorageDriver implements StorageDriverInterface
{
    /**
     * @var array
     */
    private static array $data = [];

    /**
     * @return void
     */
    public function clear(): void
    {
        self::$data = [];
    }

    /**
     * @param array $data
     * @return void
     */
    public function saveEntry(array $data): void
    {
        $index = array_search($data['uuid'], array_column(self::$data, 'uuid'));

        if ($index === false) {
            self::$data[] = $data;
            return;
        }

        self::$data[$index] = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return self::$data;
    }
}
