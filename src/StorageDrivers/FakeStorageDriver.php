<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

/**
 * In-memory storage used in tests. Entries are returned in insertion order (oldest first).
 */
class FakeStorageDriver implements StorageDriverInterface
{
    private static array $data = [];

    public function clear(): void
    {
        self::$data = [];
    }

    public function saveEntry(array $data): void
    {
        if (!isset($data['uuid'])) {
            return;
        }

        $uuid = $data['uuid'];

        // Same behaviour as the real driver: partial updates are merged into the entry
        self::$data[$uuid] = isset(self::$data[$uuid]) ? array_merge(self::$data[$uuid], $data) : $data;
    }

    public function getData(): array
    {
        return array_values(array_filter(self::$data, function (array $entry) {
            return isset($entry['type']);
        }));
    }
}
