<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\ModelPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class ModelWatcher
 */
class ModelWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'model';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen('eloquent.*', function ($event, $payload) {
            if (!$this->enabled || $this->shouldBeIgnored($event)) {
                return;
            }

            DataCollector::addToBatch(new ModelPayload($event, $payload));
        });
    }

    /**
     * Check if event should be ignored
     *
     * @param $event
     * @return bool
     */
    protected function shouldBeIgnored($event): bool
    {
        return !Str::contains($event, config('illuminar.model.trackable_events', 'eloquent.*'));
    }
}
