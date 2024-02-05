<?php

namespace Adobrovolsky97\Illuminar\Formatters;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

/**
 * Class PrimitiveArgumentFormatter
 */
class PrimitiveArgumentFormatter
{
    /**
     * @var HtmlDumper
     */
    private HtmlDumper $dumper;

    /**
     * @param HtmlDumper $dumper
     */
    public function __construct(HtmlDumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * Convert argument to primitive
     *
     * @param mixed $argument
     * @return array
     */
    public function convertToPrimitive($argument): array
    {
        try {
            if (is_array($argument)) {
                return array_map(function ($item) {
                    return $this->convertToPrimitive($item);
                }, $argument);
            }

            if ($argument instanceof Closure) {
                return [
                    'type' => 'closure',
                    'data' => serialize(new SerializableClosure($argument))
                ];
            }

            if (is_object($argument)) {
                return $this->isSerializable($argument)
                    ? [
                        'type' => 'object',
                        'data' => serialize($argument)
                    ]
                    : [
                        'type' => 'html',
                        'data' => $this->dumper->dump($argument)
                    ];
            }

            return [
                'type' => gettype($argument),
                'data' => $argument
            ];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Converting from primitive to original
     *
     * @param $payload
     * @param bool $shouldNotDump
     * @return mixed
     */
    public function convertFromPrimitive($payload, bool $shouldNotDump = false)
    {
        try {
            if ($this->isNestedArray($payload)) {
                $result = array_map(function ($item) {
                    if (isset($item['type'])) {
                        return $this->convertFromPrimitive($item, true);
                    }
                    return $item;
                }, $payload);

                return $shouldNotDump ? $result : $this->dumper->dump($result);
            }

            switch ($payload['type'] ?? '') {
                case 'html':
                    return $payload['data'];
                case 'closure':
                    $data = unserialize($payload['data'])->getClosure();
                    break;
                case 'object':
                    $data = unserialize($payload['data']);
                    break;
                default:
                    $data = is_array($payload) && array_key_exists('data', $payload) ? $payload['data'] : $payload;
                    break;
            }
        } catch (Throwable $e) {
            $data = null;
        }

        return $shouldNotDump ? $data : $this->dumper->dump($data);
    }

    /**
     * Check if array is nested
     *
     * @param array $array
     * @return bool
     */
    private function isNestedArray(array $array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }

    /**
     * Check if argument is serializable
     *
     * @param $argument
     * @return bool
     */
    private function isSerializable($argument): bool
    {
        try {
            serialize($argument);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }
}
