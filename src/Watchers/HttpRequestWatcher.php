<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\HttpRequestPayload;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;

/**
 * Class HttpClientWatcher
 */
class HttpRequestWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'http_request';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Event::listen(ResponseReceived::class, function (ResponseReceived $event) {

            if (!$this->enabled) {
                return;
            }

            $this->storageDriver->saveEntry((new HttpRequestPayload($event))->toArray());
        });
    }
}
