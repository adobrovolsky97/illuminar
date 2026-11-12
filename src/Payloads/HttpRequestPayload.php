<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class HttpRequestPayload
 */
class HttpRequestPayload extends Payload
{
    private const HIDDEN_VALUE = '********';

    /**
     * @var ResponseReceived|ConnectionFailed
     */
    private object $event;

    /**
     * @param ResponseReceived|ConnectionFailed $event
     */
    public function __construct(object $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    public function toArray(): array
    {
        $request = $this->event->request;
        $response = $this->event instanceof ResponseReceived ? $this->event->response : null;

        return array_merge($this->baseFields(HttpRequestWatcher::getName()), [
            'url'         => $request->url(),
            'method'      => $request->method(),
            'status_code' => $response ? $response->status() : null,
            'failed'      => $response === null,
            'duration'    => $response ? $this->getDuration($response) : null,
            'request'     => [
                'headers' => $this->hideHeaders($request->headers()),
                'body'    => $this->truncate($this->safe(function () use ($request) {
                    return $request->body();
                })),
                'data'    => $this->safe(function () use ($request) {
                    return $request->data();
                }),
            ],
            'response'    => $response
                ? [
                    'status'   => $response->status(),
                    'headers'  => $this->hideHeaders($response->headers()),
                    'body'     => $this->getResponseBody($response),
                    'duration' => $this->getDuration($response),
                ]
                : [
                    'error' => $this->getConnectionError(),
                ],
        ]);
    }

    /**
     * Duration in milliseconds
     */
    private function getDuration(Response $response): ?float
    {
        $seconds = $this->safe(function () use ($response) {
            return $response->handlerStats()['total_time'] ?? null;
        });

        return is_numeric($seconds) ? round($seconds * 1000, 2) : null;
    }

    /**
     * @return mixed
     */
    private function getResponseBody(Response $response)
    {
        $content = $response->body();

        if ($content !== '' && is_array($decoded = json_decode($content, true))) {
            return $decoded;
        }

        if ($response->redirect()) {
            return 'Redirected to ' . $response->header('Location');
        }

        if ($content === '') {
            return 'Empty Response';
        }

        return $this->truncate($content);
    }

    private function getConnectionError(): string
    {
        $exception = $this->event->exception ?? null;

        return $exception instanceof Throwable
            ? get_class($exception) . ': ' . $exception->getMessage()
            : 'Connection failed';
    }

    /**
     * @param mixed $content
     * @return mixed
     */
    private function truncate($content)
    {
        if (!is_string($content)) {
            return $content;
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            return '[binary data, ' . strlen($content) . ' bytes]';
        }

        $limit = (int)config('illuminar.http.max_body_size', 64) * 1024;

        return strlen($content) > $limit
            ? mb_strcut($content, 0, $limit) . "\n... [truncated, " . strlen($content) . ' bytes total]'
            : $content;
    }

    private function hideHeaders(array $headers): array
    {
        $hidden = array_map('strtolower', (array)config('illuminar.http.hidden_headers', []));

        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string)$name), $hidden, true)) {
                $headers[$name] = self::HIDDEN_VALUE;
            }
        }

        return $headers;
    }

    /**
     * @return mixed
     */
    private function safe(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            return null;
        }
    }
}
