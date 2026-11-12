<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Payloads\Payload;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;

/**
 * Class Watcher
 */
abstract class Watcher
{
    public bool $enabled = false;

    protected StorageDriverInterface $storageDriver;

    public function __construct()
    {
        $this->storageDriver = StorageDriverFactory::getDriverForConfig();
        $this->initialize();
    }

    abstract public static function getName(): string;

    /**
     * Register listeners
     */
    abstract protected function initialize(): void;

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Build payload and store it. Errors are never propagated to the application,
     * and anything which happens while building the payload is not recorded.
     *
     * @param callable(): (Payload|array|null) $factory
     */
    protected function record(callable $factory): void
    {
        Illuminar::record(function () use ($factory) {
            $payload = $factory();

            if ($payload instanceof Payload) {
                $payload = $payload->toArray();
            }

            if (!empty($payload)) {
                $this->storageDriver->saveEntry($payload);
            }
        });
    }
}
