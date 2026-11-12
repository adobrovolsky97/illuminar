<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\ExceptionPayload;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Tracks reported (logged) exceptions
 */
class ExceptionWatcher extends Watcher
{
    public static function getName(): string
    {
        return 'exception';
    }

    protected function initialize(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            if (!$this->enabled || !($event->context['exception'] ?? null) instanceof Throwable) {
                return;
            }

            $this->record(function () use ($event) {
                return new ExceptionPayload($event);
            });
        });
    }
}
