<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Events\SlowQueryFound;
use Adobrovolsky97\Illuminar\Payloads\EventPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class EventWatcher
 */
class EventWatcher extends Watcher
{
    private const FRAMEWORK_EVENTS = [
        'Illuminate\*',
        'Laravel\Octane\*',
        'Laravel\Scout\Events\ModelsImported',
        'eloquent*',
        'bootstrapped*',
        'bootstrapping*',
        'creating*',
        'composing*',
    ];

    public static function getName(): string
    {
        return 'event';
    }

    protected function initialize(): void
    {
        Event::listen('*', function (string $eventName, array $arguments) {
            if (!$this->enabled || $this->shouldBeIgnored($eventName)) {
                return;
            }

            $this->record(function () use ($eventName, $arguments) {
                return new EventPayload($eventName, $arguments);
            });
        });
    }

    protected function shouldBeIgnored(string $eventName): bool
    {
        return Str::is(
            array_merge(
                config('illuminar.events.ignore_framework_events', true) ? self::FRAMEWORK_EVENTS : [],
                (array)config('illuminar.events.ignored_events', []),
                [SlowQueryFound::class]
            ),
            $eventName
        );
    }
}
