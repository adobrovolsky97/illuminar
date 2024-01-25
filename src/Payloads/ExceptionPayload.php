<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Illuminate\Log\Events\MessageLogged;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Throwable;

/**
 * Class ExceptionPayload
 */
class ExceptionPayload extends Payload
{
    /**
     * Exception object
     *
     * @var Throwable
     */
    private Throwable $exception;

    /**
     * @param MessageLogged $event
     */
    public function __construct(MessageLogged $event)
    {
        $this->exception = $event->context['exception'];

        parent::__construct();
    }

    /**
     * @return array
     * @throws PhpVersionNotSupportedException
     */
    public function toArray(): array
    {
        return [
            'uuid'    => $this->getUuid(),
            'type'    => ExceptionWatcher::getName(),
            'class'   => get_class($this->exception),
            'file'    => $this->exception->getFile(),
            'line'    => $this->exception->getLine(),
            'trace'   => $this->exception->getTrace(),
            'message' => $this->exception->getMessage(),
            'code'    => $this->exception->getCode(),
            'caller'  => $this->exception->getFile() . ':' . $this->exception->getLine(),
            'time'      => now()->format('H:i:s'),
        ];
    }
}
