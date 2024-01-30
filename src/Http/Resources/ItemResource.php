<?php

namespace Adobrovolsky97\Illuminar\Http\Resources;

use Adobrovolsky97\Illuminar\Formatters\HtmlDumper;
use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ItemResource
 *
 * @mixin array
 */
class ItemResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function toArray($request): array
    {
        $argumentFormatter = app(PrimitiveArgumentFormatter::class);
        $dumper = app(HtmlDumper::class);

        $rawBody = $this->getBodyForDisplay();

        return [
            'type'             => $this['type'],
            'uuid'             => $this['uuid'],
            'time'             => !empty($this['time']) ? Carbon::parse($this['time'])->diffForHumans() : now()->diffForHumans(),
            'caller'           => $this['caller'] ?? null,
            'sql'              => $this['sql'] ?? null,
            'preview'          => $this['html'] ?? null,
            'color'            => $this['color'] ?? null,
            'hash'             => $this['hash'] ?? null,
            'duplicates_count' => $this['duplicates_count'] ?? null,
            'content_hash'     => md5(json_encode($rawBody)),
            'tags'             => array_filter($this->getTagsForDisplay()),
            'content'          => array_map(function ($item) use ($argumentFormatter, $dumper) {
                return $dumper->dump($argumentFormatter->convertFromPrimitive($item));
            }, $rawBody)
        ];
    }

    /**
     * Getting entities tag information for display.
     *
     * @return array
     */
    private function getTagsForDisplay(): array
    {
        switch ($this['type']) {
            case QueryWatcher::getName():
                return [
                    'connection'     => !empty($this['connection']) ? 'connection: ' . $this['connection'] : null,
                    'execution_time' => !empty($this['execution_time']) ? 'time: ' . $this['execution_time'] . 'ms' : null,
                    'is_slow'        => !empty($this['is_slow']) ? 'slow' : null,
                    'is_macro'       => !empty($this['is_macro']) ? 'macro' : null,
                    'is_duplicate'   => !empty($this['is_duplicate']) ? 'duplicate' : null,
                ];
            case HttpRequestWatcher::getName():
                return [
                    'method' => $this['method'] ?? null,
                    'status' => $this['response']['status'] ?? null,
                ];
            case CacheWatcher::getName():
                return [
                    'event' => $this['event'] ?? null,
                    'key'   => $this['key'] ?? null,
                ];
            case JobWatcher::getName():
                return [
                    'status' => $this['status'] ?? null,
                    'queue'  => !empty($this['queue']) ? 'queue: ' . $this['queue'] : null,
                ];
            case ModelWatcher::getName():
                return [
                    'action' => $this['action'] ?? null,
                    'model'  => ($this['model_class'] ?? null) . ':' . ($this['primary_key'] ?? null)
                ];
            default:
                return !empty($this['tag']) ? [$this['tag']] : [];
        }
    }

    /**
     * Get entities body for display.
     *
     * @return array
     */
    private function getBodyForDisplay(): array
    {
        switch ($this['type']) {
            case CacheWatcher::getName():
                return [$this['value'] ?? null];
            case DumpWatcher::getName():
                return $this['data'] ?? [];
            case EventWatcher::getName():
                return [$this['event'] ?? []];
            case ExceptionWatcher::getName():
                return [
                    [
                        'class'   => $this['class'] ?? null,
                        'message' => $this['message'] ?? null,
                        'code'    => $this['code'] ?? null,
                        'trace'   => $this['trace'] ?? [],
                    ]
                ];
            case HttpRequestWatcher::getName():
                return [
                    [
                        'url'      => $this['url'] ?? null,
                        'method'   => $this['method'] ?? null,
                        'request'  => $this['request'] ?? [],
                        'response' => $this['response'] ?? [],
                    ]
                ];
            case JobWatcher::getName():
                return [
                    [
                        'illuminar_uuid' => $this['uuid'] ?? null,
                        'job'            => $this['job'] ?? null,
                        'exception'      => $this['exception'] ?? null,
                    ]
                ];
            case MailWatcher::getName():
                return [
                    [
                        'subject' => $this['subject'] ?? null,
                        'queued'  => $this['queued'] ?? null,
                        'from'    => $this->getConvertedAddresses($this['from'] ?? []),
                        'to'      => $this->getConvertedAddresses($this['to'] ?? []),
                        'cc'      => $this->getConvertedAddresses($this['cc'] ?? []),
                        'bcc'     => $this->getConvertedAddresses($this['bcc'] ?? []),
                    ]
                ];
            case ModelWatcher::getName():
                return [
                    [
                        'new_attributes'      => $this['new_attributes'] ?? [],
                        'original_attributes' => $this['original_attributes'] ?? [],
                    ]
                ];
            default:
                return [];
        }
    }

    /**
     * Convert addresses to string.
     *
     * @param array $addresses
     * @return string
     */
    protected function getConvertedAddresses(array $addresses = []): string
    {
        return implode(', ', array_map(
            function ($value, $key) {
                return trim("$value $key");
            },
            $addresses,
            array_keys($addresses)
        ));
    }
}
