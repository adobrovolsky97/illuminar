<?php

namespace Adobrovolsky97\Illuminar\Http\Resources;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Support\EditorLink;
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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

/**
 * Class ItemResource
 *
 * @mixin array
 */
class ItemResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        $formatter = app(PrimitiveArgumentFormatter::class);
        $sections = $this->getBodyForDisplay();
        $time = $this->parseTime();

        return [
            'type'             => $this['type'],
            'uuid'             => $this['uuid'],
            'time'             => $time ? $time->diffForHumans() : null,
            'timestamp'        => $time ? $time->format('Y-m-d H:i:s.v') : null,
            'caller'           => $this['caller'] ?? null,
            'caller_url'       => EditorLink::forCaller($this['caller'] ?? null),
            'context'          => $this['context'] ?? null,
            'context_id'       => $this['context_id'] ?? null,
            'sql'              => $this['sql'] ?? null,
            'preview'          => $this['html'] ?? null,
            'color'            => $this['color'] ?? null,
            'hash'             => $this['hash'] ?? null,
            'duplicates_count' => $this['duplicates_count'] ?? null,
            'title'            => $this->getTitle(),
            'tags'             => array_values(array_filter($this->getTagsForDisplay(), function ($tag) {
                return $tag !== null && $tag !== '';
            })),
            'content_hash'     => md5((string)json_encode([$sections, $this['color'] ?? null, $this['tag'] ?? null])),
            'content'          => array_map(function (array $section) use ($formatter) {
                return [
                    'label' => $section['label'] ?? null,
                    'html'  => $formatter->convertFromPrimitive($section['value']),
                ];
            }, $sections),
        ];
    }

    private function parseTime(): ?Carbon
    {
        if (empty($this['time'])) {
            return null;
        }

        try {
            return Carbon::parse($this['time'])->setTimezone(config('app.timezone', 'UTC'));
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Short headline of the entry
     */
    private function getTitle(): ?string
    {
        switch ($this['type']) {
            case ExceptionWatcher::getName():
                return ($this['class'] ?? '') . ': ' . ($this['message'] ?? '');
            case HttpRequestWatcher::getName():
                return ($this['method'] ?? '') . ' ' . ($this['url'] ?? '');
            case JobWatcher::getName():
                return $this['job_class'] ?? null;
            case EventWatcher::getName():
                return $this['event_name'] ?? null;
            case MailWatcher::getName():
                return trim(($this['subject'] ?? '') . ' ' . (!empty($this['mailable_class']) ? '(' . $this['mailable_class'] . ')' : ''));
            case ModelWatcher::getName():
                return ($this['model_class'] ?? '') . (isset($this['primary_key']) ? ' #' . $this['primary_key'] : '');
            case CacheWatcher::getName():
                return $this['key'] ?? null;
            case 'log':
                return $this['message'] ?? null;
            case 'request':
                return $this->getRequestTitle();
            default:
                return null;
        }
    }

    /**
     * Getting entities tag information for display.
     */
    private function getTagsForDisplay(): array
    {
        switch ($this['type']) {
            case QueryWatcher::getName():
                return [
                    !empty($this['connection']) ? 'connection: ' . $this['connection'] : null,
                    isset($this['execution_time']) ? 'time: ' . $this['execution_time'] . 'ms' : null,
                    !empty($this['is_slow']) ? 'slow' : null,
                    !empty($this['is_macro']) ? 'macro' : null,
                    !empty($this['is_duplicate']) ? 'duplicate' : null,
                ];
            case HttpRequestWatcher::getName():
                return [
                    $this['method'] ?? null,
                    !empty($this['failed']) ? 'connection failed' : ($this['status_code'] ?? $this['response']['status'] ?? null),
                    isset($this['duration']) ? 'time: ' . $this['duration'] . 'ms' : null,
                ];
            case CacheWatcher::getName():
                return [
                    $this['event'] ?? null,
                    !empty($this['store']) ? 'store: ' . $this['store'] : null,
                    !empty($this['ttl']) ? 'ttl: ' . $this['ttl'] . 's' : null,
                ];
            case JobWatcher::getName():
                return [
                    $this['status'] ?? null,
                    !empty($this['queue']) ? 'queue: ' . $this['queue'] : null,
                    !empty($this['connection']) ? 'connection: ' . $this['connection'] : null,
                    isset($this['duration']) ? 'time: ' . $this['duration'] . 'ms' : null,
                    ($this['attempts'] ?? 0) > 1 ? 'attempts: ' . $this['attempts'] : null,
                ];
            case ModelWatcher::getName():
                return [
                    $this['action'] ?? null,
                ];
            case MailWatcher::getName():
                return [
                    !empty($this['is_preview']) ? 'preview' : null,
                    !empty($this['queued']) ? 'queued' : null,
                ];
            case ExceptionWatcher::getName():
                return [
                    $this['level'] ?? null,
                ];
            case 'log':
                return [
                    $this['level'] ?? null,
                ];
            case 'request':
                return [
                    $this['kind'] ?? null,
                    $this['status_code'] ?? ($this['status'] ?? (isset($this['exit_code']) ? 'exit: ' . $this['exit_code'] : null)),
                    isset($this['duration']) ? 'time: ' . $this['duration'] . 'ms' : null,
                    isset($this['memory']) ? 'memory: ' . $this['memory'] . 'MB' : null,
                    'queries: ' . ($this['queries_count'] ?? 0) . (!empty($this['queries_count']) ? ' (' . ($this['queries_time'] ?? 0) . 'ms)' : ''),
                    !empty($this['slow_queries']) ? 'slow queries: ' . $this['slow_queries'] : null,
                    !empty($this['n_plus_one']) ? 'N+1: ' . count($this['n_plus_one']) : null,
                ];
            default:
                return [$this['tag'] ?? null];
        }
    }

    /**
     * Sections of the entry body: label + stored value (rendered as HTML dump)
     *
     * @return array<int, array{label: ?string, value: mixed}>
     */
    private function getBodyForDisplay(): array
    {
        switch ($this['type']) {
            case CacheWatcher::getName():
                return array_key_exists('value', $this->resource) && $this['value'] !== null
                    ? [['label' => null, 'value' => $this['value']]]
                    : [];
            case DumpWatcher::getName():
                return array_map(function ($item) {
                    return ['label' => null, 'value' => $item];
                }, array_values((array)($this['data'] ?? [])));
            case EventWatcher::getName():
                return !empty($this['event']) ? [['label' => null, 'value' => $this['event']]] : [];
            case ExceptionWatcher::getName():
                return array_values(array_filter([
                    !empty($this['snippet']) ? ['label' => 'Code', 'value' => $this['snippet']] : null,
                    ['label' => 'Trace', 'value' => $this['trace'] ?? []],
                    !empty($this['previous']) ? ['label' => 'Previous', 'value' => $this['previous']] : null,
                ]));
            case HttpRequestWatcher::getName():
                return [
                    ['label' => 'Request', 'value' => $this['request'] ?? []],
                    ['label' => 'Response', 'value' => $this['response'] ?? []],
                ];
            case JobWatcher::getName():
                return array_values(array_filter([
                    ['label' => 'Job', 'value' => $this['job'] ?? null],
                    !empty($this['exception']) ? ['label' => 'Exception', 'value' => $this['exception']] : null,
                ]));
            case MailWatcher::getName():
                if (!empty($this['is_preview'])) {
                    return !empty($this['mailable']) ? [['label' => 'Mailable', 'value' => $this['mailable']]] : [];
                }

                return [
                    [
                        'label' => null,
                        'value' => array_filter([
                            'subject' => $this['subject'] ?? null,
                            'queued'  => $this['queued'] ?? null,
                            'from'    => $this->getConvertedAddresses($this['from'] ?? []),
                            'to'      => $this->getConvertedAddresses($this['to'] ?? []),
                            'cc'      => $this->getConvertedAddresses($this['cc'] ?? []),
                            'bcc'     => $this->getConvertedAddresses($this['bcc'] ?? []),
                        ], function ($value) {
                            return $value !== null && $value !== '';
                        }),
                    ],
                ];
            case ModelWatcher::getName():
                if (empty($this['new_attributes']) && empty($this['original_attributes'])) {
                    return [];
                }

                return [
                    [
                        'label' => null,
                        'value' => [
                            'new_attributes'      => $this['new_attributes'] ?? [],
                            'original_attributes' => $this['original_attributes'] ?? [],
                        ],
                    ],
                ];
            case 'log':
                return !empty($this['log_context']) ? [['label' => 'Context', 'value' => $this['log_context']]] : [];
            case 'request':
                return array_values(array_filter([
                    !empty($this['n_plus_one']) ? [
                        'label' => 'Repeated queries (possible N+1)',
                        'value' => array_map(function (array $query) {
                            return array_filter([
                                'sql'    => $query['sql'] ?? null,
                                'count'  => $query['count'] ?? null,
                                'time'   => isset($query['time_ms']) ? $query['time_ms'] . 'ms' : null,
                                'caller' => $query['caller'] ?? null,
                            ]);
                        }, $this['n_plus_one']),
                    ] : null,
                    !empty($this['stats']) ? ['label' => null, 'value' => $this['stats']] : null,
                ]));
            default:
                return [];
        }
    }

    private function getRequestTitle(): ?string
    {
        switch ($this['kind'] ?? null) {
            case 'http':
                return ($this['method'] ?? '') . ' ' . ($this['url'] ?? '') . (!empty($this['route']) ? ' (' . $this['route'] . ')' : '');
            case 'command':
                return 'artisan ' . ($this['command'] ?? '');
            case 'job':
                return $this['job_class'] ?? null;
            default:
                return $this['context'] ?? null;
        }
    }

    protected function getConvertedAddresses(?array $addresses = []): string
    {
        $addresses = (array)$addresses;

        return implode(', ', array_map(
            function ($name, $email) {
                return $name ? "$name <$email>" : (string)$email;
            },
            $addresses,
            array_keys($addresses)
        ));
    }
}
