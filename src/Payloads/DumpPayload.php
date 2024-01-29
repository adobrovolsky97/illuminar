<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use BadMethodCallException;
use Exception;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

/**
 * Class DumpPayload
 *
 * @method self red()
 * @method self orange()
 * @method self blue()
 * @method self green()
 */
class DumpPayload extends Payload
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
     * Arguments to dump
     *
     * @var array
     */
    private array $args;

    /**
     * Custom tag for dump
     *
     * @var string|null
     */
    private ?string $tag = null;

    /**
     * @var string|null
     */
    private ?string $color = null;

    /**
     * @var string
     */
    private string $caller;

    /**
     * Time of dump
     *
     * @var string
     */
    private string $time;

    /**
     * @param ...$args
     */
    public function __construct(...$args)
    {
        $this->args = $args;
        $this->time = now()->format('H:i:s');
        $this->caller = $this->getCaller();

        parent::__construct();
    }

    /**
     * This one could be changed after its being added to batch
     *
     * @return bool
     */
    public function isMutable(): bool
    {
        return true;
    }

    /**
     * @return array
     * @throws PhpVersionNotSupportedException
     */
    public function toArray(): array
    {
        $argumentFormatter = app(PrimitiveArgumentFormatter::class);

        $data = array_map(function ($arg) use ($argumentFormatter) {
            return $argumentFormatter->convertToPrimitive($arg);
        }, $this->args);

        return [
            'uuid'   => $this->getUuid(),
            'type'   => DumpWatcher::getName(),
            'data'   => $data,
            'tag'    => $this->tag,
            'caller' => $this->caller,
            'color'  => $this->color,
            'time'   => $this->time
        ];
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
            $this->color = $name;
            return $this;
        }

        throw new BadMethodCallException("Unknown method $name");
    }

    /**
     * Set custom tag to be displayed on the screen
     *
     * @param string $tag
     * @return $this
     */
    public function tag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'dump';
    }

    /**
     * Die and store data
     *
     * @return void
     */
    public function die(): void
    {
        DataCollector::storeData();
        die();
    }
}
