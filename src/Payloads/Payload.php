<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Support\Context;
use Adobrovolsky97\Illuminar\Traits\HasBacktrace;
use Illuminate\Support\Str;

/**
 * Class Payload
 */
abstract class Payload
{
    use HasBacktrace;

    /**
     * Additional paths to skip when searching for the caller
     *
     * @var array
     */
    protected array $callerPathsToRemove = [];

    protected string $uuid;

    /**
     * Full timestamp with microseconds (ISO 8601) of the moment payload was created
     */
    protected string $createdAt;

    private ?array $callerFrame = null;

    public function __construct()
    {
        $this->uuid = $this->generateUuid();
        $this->createdAt = now()->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Array representation of payload
     *
     * @return array
     */
    abstract public function toArray(): array;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    protected function generateUuid(): string
    {
        return Str::orderedUuid()->toString();
    }

    /**
     * Get caller "file:line" from stack trace
     */
    protected function getCaller(): string
    {
        $caller = $this->getCallerFrame();

        if (empty($caller)) {
            return '';
        }

        return $caller['file'] . (!empty($caller['line']) ? ':' . $caller['line'] : '');
    }

    /**
     * @return array{file?: string, line?: int}
     */
    public function getCallerFrame(): array
    {
        return $this->callerFrame ??= $this->getCallerFromStackTrace($this->callerPathsToRemove);
    }

    /**
     * Common fields of every entry
     */
    protected function baseFields(string $type): array
    {
        $context = Context::current();

        return [
            'uuid'       => $this->getUuid(),
            'type'       => $type,
            'time'       => $this->createdAt,
            'caller'     => $this->getCaller(),
            'context'    => $context['label'],
            'context_id' => $context['id'],
        ];
    }

    /**
     * Render any value to a safe HTML dump
     *
     * @param mixed $value
     */
    protected function format($value): array
    {
        return app(PrimitiveArgumentFormatter::class)->convertToPrimitive($value);
    }
}
