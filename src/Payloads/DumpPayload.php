<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;

/**
 * Class DumpPayload
 */
class DumpPayload extends Payload
{
    /**
     * Arguments to dump
     *
     * @var array
     */
    private array $args;

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
     * @return array
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
            'data'   => array_filter($data),
            'caller' => $this->caller,
            'time'   => $this->time
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'dump';
    }
}
