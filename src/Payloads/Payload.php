<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Traits\HasBacktrace;
use Illuminate\Support\Str;

/**
 * Class Payload
 */
abstract class Payload
{
    use HasBacktrace;

    /**
     * Paths to remove from stack trace
     *
     * @var array
     */
    protected array $callerPathsToRemove = [];

    /**
     * @var string
     */
    protected string $uuid;

    /**
     * Payload constructor.
     */
    public function __construct()
    {
        $this->uuid = $this->generateUuid();
    }

    /**
     * Array representation of payload
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Get unique identifier for payload
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Check if object is mutable (dump watcher could be mutable)
     *
     * @return bool
     */
    public function isMutable(): bool
    {
        return false;
    }

    /**
     * Generate unique identifier
     *
     * @return string
     */
    protected function generateUuid(): string
    {
        return Str::orderedUuid()->toString();
    }

    /**
     * Get caller from stack trace
     *
     * @return string
     */
    protected function getCaller(): string
    {
        $caller = $this->getCallerFromStackTrace($this->callerPathsToRemove);

        return !empty($caller) ? $caller['file'] . ':' . $caller['line'] : '';
    }
}
