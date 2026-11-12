<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\LogWatcher;
use Illuminate\Log\Events\MessageLogged;
use Throwable;

/**
 * Class LogPayload
 */
class LogPayload extends Payload
{
    private MessageLogged $event;

    /**
     * Log facade / logger() helper frames are skipped to show the real caller
     */
    protected array $callerPathsToRemove = ['vendor/monolog/monolog', 'vendor/psr/log'];

    public function __construct(MessageLogged $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    public function toArray(): array
    {
        $context = $this->event->context;

        // Exceptions in context are rendered as a short description, not as a huge object dump
        foreach ($context as $key => $value) {
            if ($value instanceof Throwable) {
                $context[$key] = get_class($value) . ': ' . $value->getMessage() . ' at ' . $value->getFile() . ':' . $value->getLine();
            }
        }

        return array_merge($this->baseFields(LogWatcher::getName()), [
            'level'       => (string)$this->event->level,
            'message'     => $this->message(),
            'log_context' => !empty($context) ? $this->format($context) : null,
        ]);
    }

    private function message(): string
    {
        $message = $this->event->message;

        if (is_string($message)) {
            return $message;
        }

        if (is_scalar($message) || (is_object($message) && method_exists($message, '__toString'))) {
            return (string)$message;
        }

        return (string)json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
