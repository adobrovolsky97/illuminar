<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\DumpPayload;
use Adobrovolsky97\Illuminar\Support\PendingDump;

/**
 * Class DumpWatcher
 */
class DumpWatcher extends Watcher
{
    /**
     * Store dump
     *
     * @param mixed ...$args
     */
    public function addDump(...$args): PendingDump
    {
        $payload = null;

        $this->record(function () use ($args, &$payload) {
            return $payload = new DumpPayload(...$args);
        });

        return new PendingDump($payload ? $payload->getUuid() : null);
    }

    public static function getName(): string
    {
        return 'dump';
    }

    /**
     * Dump watcher is always enabled
     */
    protected function initialize(): void
    {
        $this->enable();
    }
}
