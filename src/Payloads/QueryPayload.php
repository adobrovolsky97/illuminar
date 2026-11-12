<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Traits\SubstituteBindings;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Class QueryPayload
 */
class QueryPayload extends Payload
{
    use SubstituteBindings;

    private QueryExecuted $event;

    public function __construct(QueryExecuted $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(QueryWatcher::getName()), [
            'sql'            => $this->replaceBindings($this->event->sql, $this->event->bindings),
            'hash'           => md5($this->event->sql),
            'connection'     => $this->event->connectionName,
            'execution_time' => round((float)$this->event->time, 2),
            'is_slow'        => QueryWatcher::isSlow($this->event),
        ]);
    }
}
