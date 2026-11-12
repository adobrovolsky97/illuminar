<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

/**
 * Storage which can narrow entries itself (indexes), so the UI does not load everything on each poll.
 */
interface FiltersEntries
{
    /**
     * Entries newest first
     *
     * @param string[] $types
     */
    public function getFilteredData(array $types = [], ?string $contextId = null, ?int $limit = null): array;

    /**
     * @return array<string, int> type => count
     */
    public function countByType(?string $contextId = null): array;
}
