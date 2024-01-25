<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\DumpPayload;

class DumpWatcher extends Watcher
{
    /**
     * Add dump to batch
     *
     * @param ...$args
     * @return DumpPayload
     */
    public function addDump(...$args): DumpPayload
    {
        $entity = new DumpPayload(...$args);

        DataCollector::addToBatch($entity);

        return $entity;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'dump';
    }

    /**
     * Dump watcher is always enabled by default
     *
     * @return void
     */
    protected function initialize(): void
    {
        $this->enable();
    }
}
