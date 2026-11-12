<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\QueryPayload;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class QueryWatcher
 */
class QueryWatcher extends Watcher
{
    public static function getName(): string
    {
        return 'query';
    }

    public static function isSlow(QueryExecuted $event): bool
    {
        return $event->time >= (float)config('illuminar.queries.slow_time_ms', 500);
    }

    protected function initialize(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            if (!$this->enabled) {
                return;
            }

            $this->record(function () use ($event) {
                return $this->makePayload($event);
            });
        });
    }

    /**
     * Payload for query or null if the query must be ignored
     */
    protected function makePayload(QueryExecuted $event): ?QueryPayload
    {
        $payload = new QueryPayload($event);
        $caller = $payload->getCallerFrame();

        if (empty($caller['file']) || $this->shouldIgnoreQuery($caller['file'])) {
            return null;
        }

        return $payload;
    }

    protected function shouldIgnoreQuery(string $caller): bool
    {
        return Str::contains(str_replace('\\', '/', $caller), config('illuminar.queries.ignored_paths', []));
    }
}
