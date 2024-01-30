<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\DumpPayload;
use BadMethodCallException;

/**
 * Class DumpWatcher
 *
 * @method self red()
 * @method self orange()
 * @method self blue()
 * @method self green()
 */
class DumpWatcher extends Watcher
{
    /**
     * Colors for dump
     */
    private const COLORS = [
        'red',
        'orange',
        'blue',
        'green'
    ];

    /**
     * @var DumpPayload
     */
    private DumpPayload $dumpPayload;

    /**
     * Add dump to batch
     *
     * @param ...$args
     * @return DumpWatcher
     */
    public function addDump(...$args): self
    {
        $this->dumpPayload = new DumpPayload(...$args);

        $this->storageDriver->saveEntry($this->dumpPayload->toArray());

        return $this;
    }

    /**
     * Set colors
     *
     * @param string $name
     * @param array $arguments
     * @return self
     */
    public function __call(string $name, array $arguments): self
    {
        if (in_array($name, self::COLORS)) {
            $this->storageDriver->saveEntry(array_merge($this->dumpPayload->toArray(), ['color' => $name]));
            return $this;
        }

        throw new BadMethodCallException("Unknown method $name");
    }


    /**
     * Die and store data
     *
     * @return void
     */
    public function die(): void
    {
        die();
    }

    /**
     * Set custom tag to be displayed on the screen
     *
     * @param string $tag
     * @return $this
     */
    public function tag(string $tag): self
    {
        $this->storageDriver->saveEntry(array_merge($this->dumpPayload->toArray(), ['tag' => $tag]));
        return $this;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'dump';
    }

    /**
     * Dump watcher is always enabled by default
     *
     * @return void
     */
    protected function initialize(): void
    {
        $this->enable();
    }
}
