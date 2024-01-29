<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\QueryPayload;
use Adobrovolsky97\Illuminar\Traits\HasBacktrace;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class QueryWatcher
 */
class QueryWatcher extends Watcher
{
    use HasBacktrace;

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'query';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {

            if (!$this->enabled) {
                return;
            }

            $caller = $this->getCallerFromStackTrace();

            if (!empty($caller) && !$this->shouldIgnoreQuery($caller['file'])) {
                DataCollector::addToBatch(new QueryPayload($query));
            }
        });
    }

    /**
     * Check if query should be ignored
     *
     * @param string $caller
     * @return bool
     */
    protected function shouldIgnoreQuery(string $caller): bool
    {
        return Str::contains($caller, config('illuminar.queries.ignored_paths', []));
    }
}
