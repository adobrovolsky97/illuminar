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

    /**
     * Event object
     *
     * @var QueryExecuted
     */
    private QueryExecuted $event;

    /**
     * @param QueryExecuted $event
     */
    public function __construct(QueryExecuted $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid'           => $this->getUuid(),
            'type'           => QueryWatcher::getName(),
            'sql'            => $this->replaceBindings($this->event->sql, $this->event->bindings),
            'hash'           => md5($this->event->sql),
            'connection'     => $this->event->connectionName,
            'execution_time' => round(number_format($this->event->time, 2, '.', ''), 2),
            'is_slow'        => $this->event->time > config('illuminar.queries.slow_time_ms', 10000),
            'caller'         => $this->getCaller(),
            'time'           => now()->format('H:i:s')
        ];
    }
}
