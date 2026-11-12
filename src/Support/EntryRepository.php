<?php

namespace Adobrovolsky97\Illuminar\Support;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\StorageDrivers\FiltersEntries;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reads and filters stored entries (UI, terminal, MCP)
 */
class EntryRepository
{
    private SearchHelper $searchHelper;

    public function __construct(SearchHelper $searchHelper)
    {
        $this->searchHelper = $searchHelper;
    }

    /**
     * @param array $filters types, search, context_id, is_slow, is_duplicate, group_duplicated_queries, page, page_size
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $filters['types'] = !empty($filters['types']) ? array_values($filters['types']) : SearchHelper::TYPES;

        return $this->searchHelper->filterData($this->load($filters), $filters);
    }

    /**
     * @return array<string, int>
     */
    public function countByType(?string $contextId = null): array
    {
        $storage = StorageDriverFactory::getDriverForConfig();

        if ($storage instanceof FiltersEntries) {
            return $storage->countByType($contextId);
        }

        return $this->searchHelper->countByType(array_filter($storage->getData(), function (array $entry) use ($contextId) {
            return $contextId === null || ($entry['context_id'] ?? null) === $contextId;
        }));
    }

    public function find(string $uuid): ?array
    {
        foreach (StorageDriverFactory::getDriverForConfig()->getData() as $entry) {
            if (($entry['uuid'] ?? null) === $uuid) {
                return $entry;
            }
        }

        return null;
    }

    public function clear(): void
    {
        StorageDriverFactory::getDriverForConfig()->clear();
    }

    private function load(array $filters): array
    {
        $storage = StorageDriverFactory::getDriverForConfig();

        if ($storage instanceof FiltersEntries) {
            return $storage->getFilteredData($filters['types'] ?? [], $filters['context_id'] ?? null);
        }

        return $storage->getData();
    }
}
