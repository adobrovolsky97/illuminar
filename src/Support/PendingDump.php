<?php

namespace Adobrovolsky97\Illuminar\Support;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Illuminar;
use BadMethodCallException;

/**
 * A dump which was just stored, allows to decorate it: illuminar()->dump($x)->tag('users')->red()
 *
 * @method self red()
 * @method self orange()
 * @method self blue()
 * @method self green()
 * @method self purple()
 * @method self gray()
 */
class PendingDump
{
    public const COLORS = ['red', 'orange', 'blue', 'green', 'purple', 'gray'];

    private ?string $uuid;

    public function __construct(?string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Set custom tag to be displayed on the screen
     */
    public function tag(string $tag): self
    {
        return $this->update(['tag' => $tag]);
    }

    /**
     * Set border color
     */
    public function color(string $color): self
    {
        if (!in_array($color, self::COLORS, true)) {
            throw new BadMethodCallException("Unknown color $color, available: " . implode(', ', self::COLORS));
        }

        return $this->update(['color' => $color]);
    }

    /**
     * Stop the script execution
     *
     * @return never
     */
    public function die(): void
    {
        exit(1);
    }

    /**
     * @return self
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array($name, self::COLORS, true)) {
            return $this->color($name);
        }

        throw new BadMethodCallException("Unknown method $name");
    }

    private function update(array $data): self
    {
        if ($this->uuid === null) {
            return $this;
        }

        Illuminar::record(function () use ($data) {
            StorageDriverFactory::getDriverForConfig()->saveEntry(['uuid' => $this->uuid] + $data);
        });

        return $this;
    }
}
