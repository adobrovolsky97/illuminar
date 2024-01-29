<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

/**
 * Interface StorageDriverInterface
 */
interface StorageDriverInterface
{
    /**
     * Clear storage
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Save entry
     *
     * @param array $data
     * @return void
     */
    public function saveEntry(array $data): void;

    /**
     * Get last saved data
     *
     * @return array
     */
    public function getData(): array;
}
