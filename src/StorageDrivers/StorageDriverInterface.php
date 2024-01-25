<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

/**
 * Interface StorageDriverInterface
 */
interface StorageDriverInterface
{
    /**
     * Save data to storage
     *
     * @param array $data
     * @return void
     */
    public function save(array $data): void;

    /**
     * Append data to storage
     *
     * @param array $data
     * @return void
     */
    public function append(array $data): void;

    /**
     * Get last saved data
     *
     * @return array
     */
    public function getData(): array;
}
