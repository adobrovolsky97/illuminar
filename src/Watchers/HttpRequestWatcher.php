<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\HttpRequestPayload;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;

/**
 * Tracks outgoing requests made with the Http client
 */
class HttpRequestWatcher extends Watcher
{
    public static function getName(): string
    {
        return 'http_request';
    }

    protected function initialize(): void
    {
        Event::listen([ResponseReceived::class, ConnectionFailed::class], function (object $event) {
            if (!$this->enabled) {
                return;
            }

            $this->record(function () use ($event) {
                return new HttpRequestPayload($event);
            });
        });
    }
}
