<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;

/**
 * Class HttpRequestPayload
 */
class HttpRequestPayload extends Payload
{
    /**
     * @var ResponseReceived
     */
    private ResponseReceived $event;

    /**
     * @param ResponseReceived $event
     */
    public function __construct(ResponseReceived $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid'        => $this->getUuid(),
            'type'        => HttpRequestWatcher::getName(),
            'time'        => now()->format('H:i:s'),
            'caller'      => $this->getCaller(),
            'url'         => $this->event->request->url(),
            'method'      => $this->event->request->method(),
            'status_code' => $this->event->response->status(),
            'request'     => [
                'body'    => $this->event->request->body(),
                'headers' => $this->event->request->headers(),
                'data'    => $this->event->request->data(),
            ],
            'response'    => [
                'status'   => $this->event->response->status(),
                'body'     => $this->getResponse(),
                'headers'  => $this->event->response->headers(),
                'duration' => $this->event->response->handlerStats()['total_time'] ?? null,
            ],
        ];
    }

    /**
     * Process response body
     *
     * @return mixed|string
     */
    private function getResponse()
    {
        $content = $this->event->response->body();

        if (is_string($content)) {
            if (is_array($decodedContent = json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE) {
                return $decodedContent;
            }

            if (Str::startsWith(strtolower($this->event->response->header('Content-Type') ?? ''), 'text/plain')) {
                return $content;
            }
        }

        if ($this->event->response->redirect()) {
            return 'Redirected to ' . $this->event->response->header('Location');
        }

        return $content ?: 'Empty Response';
    }
}
