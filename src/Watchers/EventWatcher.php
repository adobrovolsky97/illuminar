<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Events\SlowQueryFound;
use Adobrovolsky97\Illuminar\Payloads\EventPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class EventWatcher
 */
class EventWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'event';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen('*', function (string $eventName, array $arguments) {
            if (!$this->enabled) {
                return;
            }

            if ($this->shouldBeIgnored($eventName)) {
                return;
            }

            DataCollector::addToBatch(new EventPayload($eventName, $arguments));
        });
    }

    /**
     * Check if event should be ignored
     *
     * @param string $eventName
     * @return bool
     */
    protected function shouldBeIgnored(string $eventName): bool
    {
        return Str::is(
            array_merge(
                (config('illuminar.events.ignore_framework_events', true)
                    ? [
                        'Illuminate\*',
                        'Laravel\Octane\*',
                        'Laravel\Scout\Events\ModelsImported',
                        'eloquent*',
                        'bootstrapped*',
                        'bootstrapping*',
                        'creating*',
                        'composing*',
                    ]
                    : []),
                config('illuminar.events.ignored_events', []),
                [SlowQueryFound::class]
            ),
            $eventName
        );
    }
}
