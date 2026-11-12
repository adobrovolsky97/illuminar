<?php

namespace Adobrovolsky97\Illuminar\Support;

/**
 * Plain text representation of stored entries (terminal, MCP / AI agents).
 */
class EntryText
{
    /**
     * Internal fields which are useless outside of the UI
     */
    private const HIDDEN_FIELDS = ['html', 'hash', 'color'];

    /**
     * One line summary of the entry
     */
    public static function summary(array $entry): string
    {
        switch ($entry['type'] ?? null) {
            case 'query':
                return sprintf(
                    '%s [%sms]%s',
                    $entry['sql'] ?? '',
                    $entry['execution_time'] ?? '?',
                    !empty($entry['is_slow']) ? ' SLOW' : (!empty($entry['is_duplicate']) ? ' DUPLICATE' : '')
                );
            case 'dump':
                return trim((!empty($entry['tag']) ? '[' . $entry['tag'] . '] ' : '') . implode(' | ', array_map(
                    [self::class, 'value'],
                    (array)($entry['data'] ?? [])
                )));
            case 'model':
                return ($entry['action'] ?? '') . ' ' . ($entry['model_class'] ?? '') . ':' . ($entry['primary_key'] ?? '');
            case 'job':
                return ($entry['job_class'] ?? '') . ' ' . ($entry['status'] ?? '')
                    . (isset($entry['duration']) ? ' [' . $entry['duration'] . 'ms]' : '');
            case 'event':
                return $entry['event_name'] ?? '';
            case 'cache':
                return ($entry['event'] ?? '') . ' ' . ($entry['key'] ?? '');
            case 'mail':
                return ($entry['subject'] ?? '') . ' ' . implode(', ', array_keys((array)($entry['to'] ?? [])));
            case 'http_request':
                return ($entry['method'] ?? '') . ' ' . ($entry['url'] ?? '') . ' ' . ($entry['status_code'] ?? 'failed');
            case 'exception':
                return ($entry['class'] ?? '') . ': ' . ($entry['message'] ?? '');
            case 'log':
                return '[' . ($entry['level'] ?? '') . '] ' . ($entry['message'] ?? '');
            case 'request':
                return self::requestSummary($entry);
            default:
                return '';
        }
    }

    public static function requestSummary(array $entry): string
    {
        switch ($entry['kind'] ?? null) {
            case 'http':
                $title = ($entry['method'] ?? '') . ' ' . ($entry['url'] ?? '') . ' ' . ($entry['status_code'] ?? '');
                break;
            case 'command':
                $title = 'artisan ' . ($entry['command'] ?? '') . ' exit:' . ($entry['exit_code'] ?? '?');
                break;
            case 'job':
                $title = 'job ' . ($entry['job_class'] ?? '') . ' ' . ($entry['status'] ?? '');
                break;
            default:
                $title = (string)($entry['context'] ?? '');
        }

        $nPlusOne = count((array)($entry['n_plus_one'] ?? []));

        return sprintf(
            '%s [%sms, %sMB, %d queries %sms%s%s]',
            trim($title),
            $entry['duration'] ?? '?',
            $entry['memory'] ?? '?',
            $entry['queries_count'] ?? 0,
            $entry['queries_time'] ?? 0,
            !empty($entry['slow_queries']) ? ', ' . $entry['slow_queries'] . ' slow' : '',
            $nPlusOne ? ', ' . $nPlusOne . ' possible N+1' : ''
        );
    }

    /**
     * Entry with all rendered HTML dumps converted to plain text
     */
    public static function details(array $entry): array
    {
        $result = [];

        foreach ($entry as $key => $value) {
            if (in_array($key, self::HIDDEN_FIELDS, true)) {
                continue;
            }

            $result[$key] = self::convert($value);
        }

        $result['summary'] = self::summary($entry);

        return $result;
    }

    /**
     * Compact representation for lists
     */
    public static function brief(array $entry): array
    {
        return array_filter([
            'uuid'       => $entry['uuid'] ?? null,
            'type'       => $entry['type'] ?? null,
            'time'       => $entry['time'] ?? null,
            'summary'    => self::summary($entry),
            'caller'     => $entry['caller'] ?? null,
            'context'    => $entry['context'] ?? null,
            'context_id' => $entry['context_id'] ?? null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Stored primitive ({type, data}) to text
     *
     * @param mixed $item
     */
    public static function value($item): string
    {
        if (!is_array($item) || !array_key_exists('data', $item)) {
            return is_scalar($item) ? (string)$item : (string)json_encode($item);
        }

        if (($item['type'] ?? null) === 'html') {
            return self::htmlToText((string)$item['data']);
        }

        return var_export($item['data'], true);
    }

    public static function htmlToText(string $html): string
    {
        $text = preg_replace('#<script.*?</script>#s', '', $html);

        return trim(html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function convert($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (isset($value['type']) && array_key_exists('data', $value) && count($value) === 2) {
            return self::value($value);
        }

        return array_map([self::class, 'convert'], $value);
    }
}
