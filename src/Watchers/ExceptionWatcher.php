<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\ExceptionPayload;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Class ExceptionWatcher
 */
class ExceptionWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'exception';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            if (!$this->enabled
                || !isset($event->context['exception'])
                || !$event->context['exception'] instanceof Throwable) {
                return;
            }
            DataCollector::addToBatch(new ExceptionPayload($event));
            DataCollector::storeData();
        });
    }
}
