<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Events\SlowQueryFound;
use Adobrovolsky97\Illuminar\Illuminar;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

/**
 * Tracking only slow queries
 *
 * Class SlowQueryWatcher
 */
class SlowQueryWatcher extends QueryWatcher
{
    public static function getName(): string
    {
        return 'slow_query';
    }

    protected function initialize(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            if (!$this->enabled || !self::isSlow($event) || Illuminar::isRecording()) {
                return;
            }

            $payload = null;

            $this->record(function () use ($event, &$payload) {
                $payload = $this->makePayload($event);

                // When all queries are tracked, the slow one is already stored (and marked as slow)
                return optional(Illuminar::getWatcher(QueryWatcher::getName()))->enabled ? null : $payload;
            });

            if ($payload !== null) {
                Event::dispatch(new SlowQueryFound($event));
            }
        });
    }
}
