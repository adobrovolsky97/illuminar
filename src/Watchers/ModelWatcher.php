<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\ModelPayload;
use Illuminate\Support\Facades\Event;

/**
 * Class ModelWatcher
 */
class ModelWatcher extends Watcher
{
    public static function getName(): string
    {
        return 'model';
    }

    protected function initialize(): void
    {
        Event::listen('eloquent.*', function (string $event, array $payload) {
            if (!$this->enabled || $this->shouldBeIgnored($event)) {
                return;
            }

            $this->record(function () use ($event, $payload) {
                return new ModelPayload($event, $payload);
            });
        });
    }

    /**
     * Event name looks like "eloquent.updated: App\Models\User"
     */
    protected function shouldBeIgnored(string $event): bool
    {
        return !in_array(
            ModelPayload::actionFromEventName($event),
            (array)config('illuminar.model.trackable_events', []),
            true
        );
    }
}
