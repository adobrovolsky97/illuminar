<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Events\SlowQueryFound;
use Adobrovolsky97\Illuminar\Payloads\QueryPayload;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

/**
 * Tracking only slow queries
 *
 * Class SlowQueryWatcher
 */
class SlowQueryWatcher extends QueryWatcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'slow_query';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {

            if (!$this->enabled || $query->time < config('illuminar.queries.slow_time_ms', 5000)) {
                return;
            }

            $caller = $this->getCallerFromStackTrace();

            if (!empty($caller) && !$this->shouldIgnoreQuery($caller['file'])) {

                Event::dispatch(new SlowQueryFound($query));
                $this->storageDriver->saveEntry((new QueryPayload($query))->toArray());
            }
        });
    }
}
