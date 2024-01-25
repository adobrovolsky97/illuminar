<?php

namespace Adobrovolsky97\Illuminar\Events;

use Illuminate\Database\Events\QueryExecuted;

/**
 * Class SlowQueryFound
 */
class SlowQueryFound
{
    /**
     * @var QueryExecuted
     */
    public QueryExecuted $query;

    /**
     * @param QueryExecuted $queryExecuted
     */
    public function __construct(QueryExecuted $queryExecuted)
    {
        $this->query = $queryExecuted;
    }
}
