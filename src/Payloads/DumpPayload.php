<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;

/**
 * Class DumpPayload
 */
class DumpPayload extends Payload
{
    /**
     * Arguments to dump
     */
    private array $args;

    /**
     * @param mixed ...$args
     */
    public function __construct(...$args)
    {
        $this->args = $args;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(DumpWatcher::getName()), [
            'data' => array_map(function ($arg) {
                return $this->format($arg);
            }, array_values($this->args)),
        ]);
    }
}
