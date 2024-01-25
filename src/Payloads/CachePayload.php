<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

/**
 * Class CachePayload
 */
class CachePayload extends Payload
{
    /**
     * Cache event
     *
     * @var object
     */
    private object $event;

    /**
     * @param object $event
     */
    public function __construct(object $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    /**
     * @return array
     * @throws PhpVersionNotSupportedException
     */
    public function toArray(): array
    {
        return [
            'uuid'   => $this->getUuid(),
            'type'   => CacheWatcher::getName(),
            'caller' => $this->getCaller(),
            'event'  => $this->getEventFromEventClass(),
            'key'    => $this->event->key,
            'tags'   => $this->event->tags ?? [],
            'value'  => !empty($this->event->value)
                ? app(PrimitiveArgumentFormatter::class)->convertToPrimitive($this->event->value)
                : null,
            'time'   => now()->format('H:i:s'),
        ];
    }

    /**
     * Mapping event class to name for display
     *
     * @return string
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
