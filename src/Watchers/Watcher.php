<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;

/**
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
     * @var StorageDriverInterface
     */
    protected StorageDriverInterface $storageDriver;

    /**
     * Watcher constructor.
     */
    public function __construct()
    {
        $this->initialize();
        $this->storageDriver = StorageDriverFactory::getDriverForConfig();
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
