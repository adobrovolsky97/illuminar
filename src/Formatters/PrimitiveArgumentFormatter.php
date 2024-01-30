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
                        'type' => 'string',
                        'data' => 'Object of class ' . get_class($argument) . ' is not serializable'
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
     * @return mixed
     */
    public function convertFromPrimitive($payload)
    {
        try {
            if (!isset($payload['type']) && is_array($payload)) {
                return array_map(function ($item) {
                    return $this->convertFromPrimitive($item);
                }, $payload);
            }

            switch ($payload['type'] ?? '') {
                case 'closure':
                    return unserialize($payload['data'])->getClosure();
                case 'object':
                    return unserialize($payload['data']);
                default:
                    return is_array($payload) && array_key_exists('data', $payload) ? $payload['data'] : $payload;
            }
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
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
