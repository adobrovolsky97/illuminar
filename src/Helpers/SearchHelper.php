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
use Illuminate\Support\Collection;

/**
 * Class SearchHelper
 */
class SearchHelper
{
    public const TYPES = [
        'dump',
        'cache',
        'model',
        'mail',
        'query',
        'event',
        'exception',
        'http_request',
        'job',
        'log',
        'request',
    ];

    private int $defaultPageSize = 100;

    /**
     * Filtering and preparing data for displaying
     */
    public function filterData(array $data, array $filters = []): LengthAwarePaginator
    {
        $data = collect($data);
        $group = (bool)($filters['group_duplicated_queries'] ?? false);

        $data = $this->markDuplicatedQueries($data, $group);

        if ($group) {
            $data = $data->unique(function ($item) {
                return $item['type'] === QueryWatcher::getName() && !empty($item['hash']) ? $item['hash'] : $item['uuid'];
            });
        }

        $data = $data->filter(function ($item) use ($filters) {
            return $this->matchesTypeFilter($item, $filters)
                && $this->matchesContextFilter($item, $filters)
                && $this->matchesSlowDuplicateFilter($item, $filters)
                && $this->matchesSearchFilter($item, $filters);
        })->values();

        $page = (int)($filters['page'] ?? 1);
        $pageSize = (int)($filters['page_size'] ?? $this->defaultPageSize);

        return new LengthAwarePaginator($data->forPage($page, $pageSize)->values(), $data->count(), $pageSize, $page);
    }

    /**
     * Count of entries per type (for the sidebar)
     */
    public function countByType(array $data): array
    {
        return collect($data)->countBy('type')->all();
    }

    private function markDuplicatedQueries(Collection $data, bool $group): Collection
    {
        $queries = $data->where('type', QueryWatcher::getName())->filter(function ($item) {
            return !empty($item['hash']);
        });

        $counts = $queries->countBy('hash');
        $times = $queries->groupBy('hash')->map(function (Collection $items) {
            return round($items->sum('execution_time'), 2);
        });

        return $data->map(function ($item) use ($counts, $times, $group) {
            if ($item['type'] !== QueryWatcher::getName() || empty($item['hash'])) {
                return $item;
            }

            $count = $counts[$item['hash']] ?? 1;
            $grouped = $group && $count > 1;

            $item['is_duplicate'] = !$group && $count > 1;
            $item['duplicates_count'] = $grouped ? $count : null;
            $item['execution_time'] = $grouped ? $times[$item['hash']] : ($item['execution_time'] ?? 0);
            $item['is_slow'] = !$grouped && ($item['is_slow'] ?? false);

            return $item;
        });
    }

    private function matchesTypeFilter(array $item, array $filters): bool
    {
        if (empty($filters['types'])) {
            return false;
        }

        return in_array($item['type'], $filters['types'], true);
    }

    private function matchesContextFilter(array $item, array $filters): bool
    {
        return empty($filters['context_id']) || ($item['context_id'] ?? null) === $filters['context_id'];
    }

    private function matchesSearchFilter(array $item, array $filters): bool
    {
        if (!isset($filters['search']) || $filters['search'] === '') {
            return true;
        }

        return str_contains($this->getSearchableStringForPayload($item), mb_strtolower($filters['search']));
    }

    private function matchesSlowDuplicateFilter(array $item, array $filters): bool
    {
        if ($item['type'] !== QueryWatcher::getName()) {
            return true;
        }

        $isSlow = (bool)($filters['is_slow'] ?? false);
        $isDuplicate = (bool)($filters['is_duplicate'] ?? false);
        $itemIsDuplicate = ($item['is_duplicate'] ?? false) || ($item['duplicates_count'] ?? 0) > 1;

        if ($isSlow && !($item['is_slow'] ?? false)) {
            return false;
        }

        return !$isDuplicate || $itemIsDuplicate;
    }

    private function getSearchableStringForPayload(array $payload): string
    {
        switch ($payload['type']) {
            case DumpWatcher::getName():
                $searchableData = [
                    $payload['tag'] ?? null,
                    $payload['color'] ?? null,
                    $this->dumpText($payload['data'] ?? []),
                ];
                break;
            case CacheWatcher::getName():
                $searchableData = [
                    $payload['event'] ?? null,
                    $payload['key'] ?? null,
                    $payload['store'] ?? null,
                ];
                break;
            case ModelWatcher::getName():
                $searchableData = [
                    $payload['action'] ?? null,
                    ($payload['model_class'] ?? null) . ':' . ($payload['primary_key'] ?? null),
                ];
                break;
            case QueryWatcher::getName():
                $searchableData = [
                    $payload['sql'] ?? null,
                    $payload['connection'] ?? null,
                    $payload['hash'] ?? null,
                ];
                break;
            case EventWatcher::getName():
                $searchableData = [
                    $payload['event_name'] ?? null,
                ];
                break;
            case MailWatcher::getName():
                $searchableData = [
                    $payload['mailable_class'] ?? null,
                    $payload['subject'] ?? null,
                    implode(' ', array_keys((array)($payload['to'] ?? []))),
                ];
                break;
            case HttpRequestWatcher::getName():
                $searchableData = [
                    $payload['url'] ?? null,
                    $payload['method'] ?? null,
                    $payload['status_code'] ?? null,
                ];
                break;
            case JobWatcher::getName():
                $searchableData = [
                    $payload['job_class'] ?? null,
                    $payload['uuid'] ?? null,
                    $payload['status'] ?? null,
                    $payload['queue'] ?? null,
                ];
                break;
            case ExceptionWatcher::getName():
                $searchableData = [
                    $payload['class'] ?? null,
                    $payload['message'] ?? null,
                    ($payload['file'] ?? '') . ':' . ($payload['line'] ?? ''),
                ];
                break;
            case 'log':
                $searchableData = [
                    $payload['level'] ?? null,
                    $payload['message'] ?? null,
                ];
                break;
            case 'request':
                $searchableData = [
                    $payload['method'] ?? null,
                    $payload['url'] ?? null,
                    $payload['status_code'] ?? null,
                    $payload['route'] ?? null,
                    $payload['command'] ?? null,
                    $payload['job_class'] ?? null,
                    !empty($payload['n_plus_one']) ? 'n+1' : null,
                ];
                break;
            default:
                $searchableData = [];
        }

        $searchableData[] = $payload['caller'] ?? null;
        $searchableData[] = $payload['context'] ?? null;

        return mb_strtolower(implode(' ', array_filter($searchableData, function ($value) {
            return $value !== null && $value !== '';
        })));
    }

    /**
     * Plain text of dumped values
     */
    private function dumpText(array $data): string
    {
        return implode(' ', array_map(function ($item) {
            if (!is_array($item)) {
                return '';
            }

            $value = $item['data'] ?? '';

            if (($item['type'] ?? null) === 'html') {
                return html_entity_decode(strip_tags((string)preg_replace('#<script.*?</script>#s', '', (string)$value)));
            }

            return is_scalar($value) ? (string)$value : '';
        }, $data));
    }
}
