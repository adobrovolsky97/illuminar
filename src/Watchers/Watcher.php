<?php

namespace Adobrovolsky97\Illuminar\Watchers;

/**
 * Base watcher
 *
 * Class Watcher
 */
abstract class Watcher
{
    /**
     * Watcher
     *
     * @var bool
     */
    public bool $enabled = false;

    /**
     * Watcher constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Initialize watcher
     *
     * @return void
     */
    abstract protected function initialize(): void;

    /**
     * Enable watcher
     *
     * @return $this
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable watcher
     *
     * @return $this
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }
}
