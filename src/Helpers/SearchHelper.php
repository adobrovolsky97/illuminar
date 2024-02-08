<?php

namespace Adobrovolsky97\Illuminar\Helpers;

use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class SearchHelper
 */
class SearchHelper
{
    /**
     * @var int
     */
    private int $defaultPageSize = 100;

    /**
     * Filtering and preparing data for displaying
     *
     * @param array $data
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function filterData(array $data, array $filters = []): LengthAwarePaginator
    {
        $data = collect($data);

        $hashes = $data->where('type', QueryWatcher::getName())->pluck('hash')->countBy();

        $data = $data->map(function ($item) use ($hashes, $filters, $data) {
            if ($item['type'] === QueryWatcher::getName() && isset($item['hash'])) {
                $item['is_duplicate'] = !($filters['group_duplicated_queries'] ?? false) && $hashes[$item['hash']] > 1;

                $item['duplicates_count'] = ($filters['group_duplicated_queries'] ?? false) && $hashes[$item['hash']] > 1
                    ? $hashes[$item['hash']]
                    : null;

                $item['execution_time'] = ($filters['group_duplicated_queries'] ?? false) && $hashes[$item['hash']] > 1
                    ? $data->where('hash', $item['hash'])->sum('execution_time')
                    : ($item['execution_time'] ?? 0);

                $item['is_slow'] = ($filters['group_duplicated_queries'] ?? false) && $hashes[$item['hash']] > 1
                    ? false
                    : $item['is_slow'] ?? false;
            }

            return $item;
        });

        if ($filters['group_duplicated_queries'] ?? false) {
            $data = $data->unique(function ($item) {
                return $item['type'] === QueryWatcher::getName() && !empty($item['hash']) ? $item['hash'] : $item['uuid'];
            });
        }

        $data = $data->filter(function ($item) use ($filters) {
            return $this->matchesTypeFilter($item, $filters) &&
                $this->matchesSearchFilter($item, $filters) &&
                $this->matchesSlowDuplicateFilter($item, $filters);
        });

        $page = $filters['page'] ?? 1;
        $pageSize = $filters['page_size'] ?? $this->defaultPageSize;

        return new LengthAwarePaginator($data->forPage($page, $pageSize), $data->count(), $pageSize, $page);
    }

    /**
     * Check if item matches type filter
     *
     * @param array $item
     * @param array $filters
     * @return bool
     */
    private function matchesTypeFilter(array $item, array $filters): bool
    {
        if (empty($filters['types'])) {
            return false;
        }
        return in_array($item['type'], $filters['types']);
    }

    /**
     * Check if item matches search filter
     *
     * @param array $item
     * @param array $filters
     * @return bool
     */
    private function matchesSearchFilter(array $item, array $filters): bool
    {
        return empty($filters['search']) || str_contains($this->getSearchableStringForPayload($item), strtolower($filters['search']));
    }

    /**
     * Check if item matches slow and duplicate filter
     *
     * @param array $item
     * @param array $filters
     * @return bool
     */
    private function matchesSlowDuplicateFilter(array $item, array $filters): bool
    {
        if ($item['type'] !== QueryWatcher::getName()) {
            return true;
        }

        $isSlow = $filters['is_slow'] ?? false;
        $isDuplicate = $filters['is_duplicate'] ?? false;

        switch (true) {
            case $isSlow && $isDuplicate:
                return ($item['is_slow'] ?? false) && ($item['is_duplicate'] ?? false);
            case $isSlow:
                return $item['is_slow'] ?? false;
            case $isDuplicate:
                return ($item['is_duplicate'] ?? false) || ($item['duplicates_count'] ?? 0) > 1;
            default:
                return true;
        }
    }

    /**
     * Get searchable string for payload
     *
     * @param array $payload
     * @return string
     */
    private function getSearchableStringForPayload(array $payload): string
    {
        switch ($payload['type']) {
            case DumpWatcher::getName():
                $searchableData = [
                    $payload['tag'] ?? null,
                    $payload['color'] ?? null,
                    $payload['caller'] ?? null
                ];
                break;
            case CacheWatcher::getName():
                $searchableData = [
                    $payload['event'] ?? null,
                    $payload['key'] ?? null,
                    $payload['caller'] ?? null
                ];
                break;
            case ModelWatcher::getName():
                $searchableData = [
                    $payload['action'] ?? null,
                    ($payload['model_class'] ?? null) . ':' . ($payload['primary_key'] ?? null),
                    $payload['caller'] ?? null
                ];
                break;
            case QueryWatcher::getName():
                $searchableData = [
                    $payload['sql'] ?? null,
                    $payload['caller'] ?? null,
                    $payload['connection'] ?? null,
                    $payload['hash'] ?? null,
                ];
                break;
            case EventWatcher::getName():
                $searchableData = [
                    $payload['event_name'] ?? null,
                    $payload['caller'] ?? null,
                ];
                break;
            case MailWatcher::getName():
                $searchableData = [
                    $payload['mailable_class'] ?? null,
                    $payload['subject'] ?? null,
                    $payload['caller'] ?? null,
                ];
                break;
            case HttpRequestWatcher::getName():
                $searchableData = [
                    $payload['url'] ?? null,
                    $payload['method'] ?? null,
                    $payload['status_code'] ?? null,
                    $payload['caller'] ?? null,
                ];
                break;
            case JobWatcher::getName():
                $searchableData = [
                    $payload['job_class'] ?? null,
                    $payload['uuid'] ?? null,
                    $payload['status'] ?? null,
                    $payload['queue'] ?? null,
                    $payload['caller'] ?? null,
                ];
                break;
            case ExceptionWatcher::getName():
                $searchableData = [
                    $payload['class'] ?? null,
                    ($payload['file'] ?? null) . ':' . $payload['line'] ?? null,
                    $payload['caller'] ?? null,
                ];
                break;
            default:
                $searchableData = [];
        }

        return strtolower(implode(' ', array_filter($searchableData)));
    }
}
