<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;

/**
 * Class CachePayload
 */
class CachePayload extends Payload
{
    private object $event;

    public function __construct(object $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(CacheWatcher::getName()), [
            'event' => $this->getEventFromEventClass(),
            'key'   => $this->event->key ?? null,
            'store' => $this->event->storeName ?? null,
            'tags'  => $this->event->tags ?? [],
            'ttl'   => $this->event->seconds ?? null,
            'value' => property_exists($this->event, 'value') ? $this->format($this->event->value) : null,
        ]);
    }

    /**
     * Mapping event class to name for display
     */
    private function getEventFromEventClass(): string
    {
        switch (get_class($this->event)) {
            case CacheHit::class:
                return 'hit';
            case CacheMissed::class:
                return 'missed';
            case KeyWritten::class:
                return 'written';
            case KeyForgotten::class:
                return 'forgotten';
        }

        return 'unknown';
    }
}
