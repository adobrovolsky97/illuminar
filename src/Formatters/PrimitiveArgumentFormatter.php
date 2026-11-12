<?php

namespace Adobrovolsky97\Illuminar\Formatters;

use Throwable;

/**
 * Converts values to a storable representation.
 *
 * Objects, closures and arrays are rendered to an HTML dump at capture time, so the UI never
 * needs to unserialize() data from the storage (which was slow, could fail when classes change
 * and allowed PHP object injection). Scalars are stored as is.
 */
class PrimitiveArgumentFormatter
{
    private HtmlDumper $dumper;

    public function __construct(HtmlDumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * Convert argument to storable primitive
     *
     * @param mixed $argument
     * @return array{type: string, data: mixed}
     */
    public function convertToPrimitive($argument): array
    {
        if (is_null($argument) || is_scalar($argument)) {
            return [
                'type' => gettype($argument),
                'data' => $argument,
            ];
        }

        try {
            return [
                'type' => 'html',
                'data' => $this->dumper->dump($argument),
            ];
        } catch (Throwable $e) {
            return [
                'type' => 'html',
                'data' => $this->dumper->dump('Illuminar: unable to dump value: ' . $e->getMessage()),
            ];
        }
    }

    /**
     * Convert stored primitive to HTML for display
     *
     * @param mixed $payload
     * @return string
     */
    public function convertFromPrimitive($payload): string
    {
        try {
            if (is_array($payload) && isset($payload['type']) && array_key_exists('data', $payload) && count($payload) === 2) {
                if ($payload['type'] === 'html') {
                    return (string)$payload['data'];
                }

                // Never unserialize stored data
                if (in_array($payload['type'], ['object', 'closure'], true)) {
                    return $this->dumper->dump('Entry was stored by an older Illuminar version, please clear the data.');
                }

                return $this->dumper->dump($payload['data']);
            }

            return $this->dumper->dump($payload);
        } catch (Throwable $e) {
            return '';
        }
    }
}
