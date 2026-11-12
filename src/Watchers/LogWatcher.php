<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Payloads\LogPayload;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Tracks log messages: Log::info(), logger(), Log::channel('x')->warning() etc.
 */
class LogWatcher extends Watcher
{
    /**
     * PSR-3 levels ordered by severity
     */
    public const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    public static function getName(): string
    {
        return 'log';
    }

    protected function initialize(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            if (!$this->enabled || !$this->shouldRecord($event)) {
                return;
            }

            $this->record(function () use ($event) {
                return new LogPayload($event);
            });
        });
    }

    private function shouldRecord(MessageLogged $event): bool
    {
        // Reported exceptions are shown by the exception watcher (with trace and code snippet)
        if (($event->context['exception'] ?? null) instanceof Throwable
            && optional(Illuminar::getWatcher(ExceptionWatcher::getName()))->enabled) {
            return false;
        }

        $minLevel = array_search(strtolower((string)config('illuminar.logs.min_level', 'debug')), self::LEVELS, true);
        $level = array_search(strtolower((string)$event->level), self::LEVELS, true);

        return $level === false || $minLevel === false || $level >= $minLevel;
    }
}
