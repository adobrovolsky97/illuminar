<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\HttpRequestPayload;
use Event;
use Illuminate\Http\Client\Events\ResponseReceived;

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

            DataCollector::addToBatch(new HttpRequestPayload($event));
        });
    }
}
