<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

/**
 * Class EventPayload
 */
class EventPayload extends Payload
{
    /**
     * Event name
     *
     * @var string
     */
    private string $eventName;

    /**
     * Event object itself
     *
     * @var mixed
     */
    private $event;

    /**
     * @param string $eventName
     * @param array $arguments
     */
    public function __construct(string $eventName, array $arguments = [])
    {
        $this->eventName = $eventName;

        $this->event = class_exists($eventName) && isset($arguments[0]) && is_object($arguments[0])
            ? $arguments[0]
            : $arguments;

        parent::__construct();
    }

    /**
     * @return array
     * @throws PhpVersionNotSupportedException
     */
    public function toArray(): array
    {
        return [
            'uuid'       => $this->getUuid(),
            'type'       => EventWatcher::getName(),
            'event_name' => $this->eventName,
            'event'      => $this->event
                ? app(PrimitiveArgumentFormatter::class)->convertToPrimitive($this->event)
                : null,
            'caller'     => $this->getCaller(),
            'time'       => now()->format('H:i:s'),
        ];
    }
}
