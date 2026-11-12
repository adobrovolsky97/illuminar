<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Traits\SubstituteBindings;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class QueryMacroPayload
 */
class QueryMacroPayload extends Payload
{
    use SubstituteBindings;

    /**
     * @var EloquentBuilder|QueryBuilder|Relation
     */
    private $builder;

    /**
     * @param EloquentBuilder|QueryBuilder|Relation $builder
     */
    public function __construct($builder)
    {
        $this->builder = $builder;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(QueryWatcher::getName()), [
            'sql'        => $this->replaceBindings($this->builder->toSql(), $this->builder->getBindings()),
            'connection' => $this->builder->getConnection()->getName(),
            'is_macro'   => true,
        ]);
    }
}
