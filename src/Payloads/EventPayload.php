<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\EventWatcher;

/**
 * Class EventPayload
 */
class EventPayload extends Payload
{
    private string $eventName;

    /**
     * Event object itself or event arguments for string events
     *
     * @var mixed
     */
    private $event;

    public function __construct(string $eventName, array $arguments = [])
    {
        $this->eventName = $eventName;

        $this->event = class_exists($eventName) && isset($arguments[0]) && is_object($arguments[0])
            ? $arguments[0]
            : $arguments;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(EventWatcher::getName()), [
            'event_name' => $this->eventName,
            'event'      => $this->event !== [] && $this->event !== null ? $this->format($this->event) : null,
        ]);
    }
}
