<?php

namespace Adobrovolsky97\Illuminar\Mcp;

use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\Support\EntryRepository;
use Adobrovolsky97\Illuminar\Support\EntryText;
use Throwable;

/**
 * Minimal Model Context Protocol server (JSON-RPC 2.0) exposing Illuminar data to AI agents.
 *
 * Transport agnostic: handle() takes a decoded message and returns a response (or null for notifications).
 */
class McpServer
{
    public const SUPPORTED_PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    private const MAX_LIMIT = 200;

    private EntryRepository $entries;

    public function __construct(EntryRepository $entries)
    {
        $this->entries = $entries;
    }

    public function handle(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? null;

        // Notifications (no id) never get a response
        if (!array_key_exists('id', $message)) {
            return null;
        }

        try {
            switch ($method) {
                case 'initialize':
                    return $this->result($id, $this->initialize($message['params'] ?? []));
                case 'ping':
                    return $this->result($id, new \stdClass());
                case 'tools/list':
                    return $this->result($id, ['tools' => $this->tools()]);
                case 'tools/call':
                    return $this->result($id, $this->callTool($message['params'] ?? []));
                default:
                    return $this->error($id, -32601, "Method not found: $method");
            }
        } catch (Throwable $exception) {
            return $this->error($id, -32603, $exception->getMessage());
        }
    }

    private function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => in_array($requested, self::SUPPORTED_PROTOCOL_VERSIONS, true)
                ? $requested
                : self::SUPPORTED_PROTOCOL_VERSIONS[0],
            'capabilities'    => ['tools' => new \stdClass()],
            'serverInfo'      => ['name' => 'illuminar', 'version' => '2.0.0'],
            'instructions'    => 'Illuminar records debug data of this Laravel application: dumps (illuminar($x)), SQL queries, '
                . 'request profiles with N+1 detection, exceptions, logs, jobs, events, cache, mails and outgoing HTTP requests. '
                . 'Start with get_last_exception or list_requests, then use get_entry / list_entries with context_id to see '
                . 'everything that happened during one request.',
        ];
    }

    private function tools(): array
    {
        return [
            [
                'name'        => 'list_entries',
                'description' => 'List recorded Illuminar entries (newest first) with optional filters. Returns short summaries; use get_entry for details.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'types'           => [
                            'type'        => 'array',
                            'items'       => ['type' => 'string', 'enum' => SearchHelper::TYPES],
                            'description' => 'Entry types, all when omitted',
                        ],
                        'search'          => ['type' => 'string', 'description' => 'Case-insensitive text search (SQL, dump content, class, url, caller...)'],
                        'context_id'      => ['type' => 'string', 'description' => 'Only entries of one request / command / job'],
                        'only_slow'       => ['type' => 'boolean', 'description' => 'Only slow queries'],
                        'only_duplicates' => ['type' => 'boolean', 'description' => 'Only duplicated queries (N+1)'],
                        'limit'           => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'default' => 30],
                    ],
                ],
            ],
            [
                'name'        => 'get_entry',
                'description' => 'Full details of one entry (dump values, SQL, trace, request/response, job payload...) as plain text.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'uuid'            => ['type' => 'string'],
                        'include_context' => ['type' => 'boolean', 'description' => 'Also list other entries of the same request / command / job', 'default' => false],
                    ],
                    'required'   => ['uuid'],
                ],
            ],
            [
                'name'        => 'list_requests',
                'description' => 'Profiles of recent HTTP requests, artisan commands and jobs: status, duration, memory, query count and repeated queries (possible N+1) with the place in code.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'only_problems' => ['type' => 'boolean', 'description' => 'Only failed / 5xx / slow queries / N+1', 'default' => false],
                        'search'        => ['type' => 'string', 'description' => 'Filter by url, route, command or job class'],
                        'limit'         => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'default' => 20],
                    ],
                ],
            ],
            [
                'name'        => 'get_last_exception',
                'description' => 'The latest recorded exception with code snippet, trace and all entries (queries, dumps, logs) of the request it happened in.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name'        => 'clear_entries',
                'description' => 'Remove all recorded entries, e.g. before reproducing a bug to get clean data.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
        ];
    }

    private function callTool(array $params): array
    {
        $arguments = (array)($params['arguments'] ?? []);

        switch ($params['name'] ?? null) {
            case 'list_entries':
                return $this->text($this->listEntries($arguments));
            case 'get_entry':
                return $this->getEntry($arguments);
            case 'list_requests':
                return $this->text($this->listRequests($arguments));
            case 'get_last_exception':
                return $this->getLastException();
            case 'clear_entries':
                $this->entries->clear();

                return $this->text(['cleared' => true]);
            default:
                return $this->text(['error' => 'Unknown tool: ' . ($params['name'] ?? '')], true);
        }
    }

    private function listEntries(array $arguments): array
    {
        $types = array_values(array_intersect((array)($arguments['types'] ?? []), SearchHelper::TYPES));

        $paginator = $this->entries->search([
            'types'        => $types,
            'search'       => $arguments['search'] ?? null,
            'context_id'   => $arguments['context_id'] ?? null,
            'is_slow'      => (bool)($arguments['only_slow'] ?? false),
            'is_duplicate' => (bool)($arguments['only_duplicates'] ?? false),
            'page_size'    => $this->limit($arguments, 30),
        ]);

        return [
            'total'   => $paginator->total(),
            'entries' => array_map([EntryText::class, 'brief'], $paginator->items()),
        ];
    }

    private function getEntry(array $arguments): array
    {
        $entry = $this->entries->find((string)($arguments['uuid'] ?? ''));

        if ($entry === null) {
            return $this->text(['error' => 'Entry not found, it could be removed. Use list_entries to get actual uuids.'], true);
        }

        $result = ['entry' => EntryText::details($entry)];

        if (!empty($arguments['include_context']) && !empty($entry['context_id'])) {
            $result['context_entries'] = $this->contextEntries($entry['context_id']);
        }

        return $this->text($result);
    }

    private function listRequests(array $arguments): array
    {
        $onlyProblems = (bool)($arguments['only_problems'] ?? false);
        $limit = $this->limit($arguments, 20);

        $requests = $this->entries->search([
            'types'     => ['request'],
            'search'    => $arguments['search'] ?? null,
            'page_size' => $onlyProblems ? 500 : $limit,
        ])->items();

        if ($onlyProblems) {
            $requests = array_values(array_filter($requests, function (array $entry) {
                return !empty($entry['n_plus_one'])
                    || !empty($entry['slow_queries'])
                    || (int)($entry['status_code'] ?? 0) >= 500
                    || ($entry['status'] ?? null) === 'failed'
                    || (int)($entry['exit_code'] ?? 0) !== 0
                    || !empty($entry['stats']['exceptions']);
            }));
        }

        return [
            'requests' => array_map(function (array $entry) {
                return array_filter([
                    'uuid'         => $entry['uuid'],
                    'summary'      => EntryText::summary($entry),
                    'time'         => $entry['time'] ?? null,
                    'context_id'   => $entry['context_id'] ?? null,
                    'n_plus_one'   => $entry['n_plus_one'] ?? null,
                    'stats'        => $entry['stats'] ?? null,
                ]);
            }, array_slice($requests, 0, $limit)),
        ];
    }

    private function getLastException(): array
    {
        $exception = $this->entries->search(['types' => ['exception'], 'page_size' => 1])->items()[0] ?? null;

        if ($exception === null) {
            return $this->text(['message' => 'No exceptions recorded. Exception tracking is enabled with ILLUMINAR_TRACK_EXCEPTIONS=true.']);
        }

        return $this->text([
            'exception'       => EntryText::details($exception),
            'context_entries' => !empty($exception['context_id']) ? $this->contextEntries($exception['context_id']) : [],
        ]);
    }

    private function contextEntries(string $contextId): array
    {
        return array_map(
            [EntryText::class, 'brief'],
            array_reverse($this->entries->search(['context_id' => $contextId, 'page_size' => 200])->items())
        );
    }

    private function limit(array $arguments, int $default): int
    {
        return max(1, min(self::MAX_LIMIT, (int)($arguments['limit'] ?? $default)));
    }

    private function text($data, bool $isError = false): array
    {
        return [
            'content' => [[
                'type' => 'text',
                'text' => (string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            ]],
            'isError' => $isError,
        ];
    }

    private function result($id, $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error($id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
