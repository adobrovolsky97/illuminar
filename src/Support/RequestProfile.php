<?php

namespace Adobrovolsky97\Illuminar\Support;

use Adobrovolsky97\Illuminar\Traits\HasBacktrace;
use Adobrovolsky97\Illuminar\Traits\SubstituteBindings;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Collects statistics of one request / command / job: queries (with N+1 detection),
 * cache, outgoing HTTP requests, mails, logs and exceptions.
 */
class RequestProfile
{
    use HasBacktrace;
    use SubstituteBindings;

    public string $contextId;

    public string $label;

    public float $startedAt;

    public int $queries = 0;

    public float $queriesTime = 0.0;

    public int $slowQueries = 0;

    public int $cacheHits = 0;

    public int $cacheMisses = 0;

    public int $cacheWrites = 0;

    public int $httpRequests = 0;

    public int $mails = 0;

    public int $logs = 0;

    public int $exceptions = 0;

    public int $jobsDispatched = 0;

    /**
     * Same SQL (without bindings) => stats
     *
     * @var array<string, array{sql: string, example: string, count: int, time: float, caller: ?string}>
     */
    private array $queryGroups = [];

    public function __construct(string $contextId, string $label, ?float $startedAt = null)
    {
        $this->contextId = $contextId;
        $this->label = $label;
        $this->startedAt = $startedAt ?? microtime(true);
    }

    public function addQuery(QueryExecuted $event, bool $isSlow): void
    {
        $this->queries++;
        $this->queriesTime += (float)$event->time;
        $this->slowQueries += $isSlow ? 1 : 0;

        $hash = md5($event->sql);

        if (!isset($this->queryGroups[$hash])) {
            $this->queryGroups[$hash] = [
                'sql'     => $event->sql,
                'example' => '',
                'count'   => 0,
                'time'    => 0.0,
                'caller'  => null,
            ];
        }

        $group = &$this->queryGroups[$hash];
        $group['count']++;
        $group['time'] += (float)$event->time;

        // The backtrace is expensive, so it is taken only once the query starts repeating
        if ($group['count'] === 2) {
            $caller = $this->getCallerFromStackTrace();
            $group['caller'] = !empty($caller) ? $caller['file'] . (!empty($caller['line']) ? ':' . $caller['line'] : '') : null;
            $group['example'] = $this->replaceBindings($event->sql, $event->bindings);
        }
    }

    /**
     * Queries executed at least $threshold times - most likely N+1 problems
     */
    public function repeatedQueries(int $threshold): array
    {
        $groups = array_values(array_filter($this->queryGroups, function (array $group) use ($threshold) {
            return $group['count'] >= $threshold;
        }));

        usort($groups, function (array $a, array $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_map(function (array $group) {
            return [
                'sql'     => $group['example'] ?: $group['sql'],
                'count'   => $group['count'],
                'time_ms' => round($group['time'], 2),
                'caller'  => $group['caller'],
            ];
        }, $groups);
    }

    public function durationMs(): float
    {
        return round((microtime(true) - $this->startedAt) * 1000, 2);
    }
}
