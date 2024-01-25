<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Traits\SubstituteBindings;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Class QueryMacroPayload
 */
class QueryMacroPayload extends Payload
{
    use SubstituteBindings;

    /**
     * @var EloquentBuilder|QueryBuilder
     */
    private $builder;

    /**
     * @param EloquentBuilder|QueryBuilder $builder
     */
    public function __construct($builder)
    {
        $this->builder = $builder;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid'       => $this->getUuid(),
            'sql'        => $this->replaceBindings($this->builder->toSql(), $this->builder->getBindings()),
            'connection' => $this->builder->getConnection()->getName(),
            'is_macro'   => true,
            'type'       => QueryWatcher::getName(),
            'caller'     => $this->getCaller(),
        ];
    }
}
