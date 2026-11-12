<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\CachePayload;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;

/**
 * Class CacheWatcher
 */
class CacheWatcher extends Watcher
{
    public static function getName(): string
    {
        return 'cache';
    }

    protected function initialize(): void
    {
        Event::listen([
            CacheHit::class,
            CacheMissed::class,
            KeyWritten::class,
            KeyForgotten::class,
        ], function (object $event) {
            if (!$this->enabled) {
                return;
            }

            $this->record(function () use ($event) {
                return new CachePayload($event);
            });
        });
    }
}
