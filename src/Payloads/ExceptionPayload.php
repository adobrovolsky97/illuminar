<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Illuminate\Log\Events\MessageLogged;
use Throwable;

/**
 * Class ExceptionPayload
 */
class ExceptionPayload extends Payload
{
    private const MAX_TRACE_FRAMES = 50;
    private const SNIPPET_LINES = 5;

    private Throwable $exception;

    private ?string $level;

    /**
     * @param MessageLogged|Throwable $event
     */
    public function __construct($event)
    {
        $this->exception = $event instanceof Throwable ? $event : $event->context['exception'];
        $this->level = $event instanceof MessageLogged ? $event->level : null;

        parent::__construct();
    }

    public function toArray(): array
    {
        $exception = $this->exception;

        return array_merge($this->baseFields(ExceptionWatcher::getName()), [
            'class'    => get_class($exception),
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
            'level'    => $this->level,
            'message'  => $exception->getMessage(),
            'code'     => $exception->getCode(),
            'snippet'  => $this->getSnippet($exception->getFile(), $exception->getLine()),
            'trace'    => $this->getTrace($exception),
            'previous' => $this->getPrevious($exception),
            // Exception place is more useful than the place where it was logged
            'caller'   => $exception->getFile() . ':' . $exception->getLine(),
        ]);
    }

    /**
     * Trace without arguments (they may contain huge objects and secrets)
     */
    private function getTrace(Throwable $exception): array
    {
        return array_map(function (array $frame) {
            $function = isset($frame['class'])
                ? $frame['class'] . ($frame['type'] ?? '::') . $frame['function']
                : ($frame['function'] ?? '');

            return trim(($frame['file'] ?? '[internal]') . (isset($frame['line']) ? ':' . $frame['line'] : '') . ' ' . $function . '()');
        }, array_slice($exception->getTrace(), 0, self::MAX_TRACE_FRAMES));
    }

    private function getPrevious(Throwable $exception): array
    {
        $previous = [];

        while (($exception = $exception->getPrevious()) && count($previous) < 5) {
            $previous[] = get_class($exception) . ': ' . $exception->getMessage() . ' at ' . $exception->getFile() . ':' . $exception->getLine();
        }

        return $previous;
    }

    /**
     * Source lines around the line which thrown the exception
     *
     * @return array<int, string> line number => code
     */
    private function getSnippet(string $file, int $line): array
    {
        if (!is_file($file) || !is_readable($file)) {
            return [];
        }

        try {
            $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        } catch (Throwable $e) {
            return [];
        }

        $start = max(1, $line - self::SNIPPET_LINES);
        $snippet = [];

        for ($number = $start; $number <= min(count($lines), $line + self::SNIPPET_LINES); $number++) {
            $snippet[$number] = ($number === $line ? '> ' : '  ') . $lines[$number - 1];
        }

        return $snippet;
    }
}
