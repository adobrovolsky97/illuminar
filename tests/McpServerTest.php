<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Mcp\McpServer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Class McpServerTest
 */
class McpServerTest extends TestCase
{
    private McpServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = app(McpServer::class);
    }

    public function testInitialize(): void
    {
        $response = $this->server->handle([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'initialize',
            'params'  => ['protocolVersion' => '2025-03-26', 'capabilities' => [], 'clientInfo' => ['name' => 'test']],
        ]);

        $this->assertSame('2025-03-26', $response['result']['protocolVersion']);
        $this->assertSame('illuminar', $response['result']['serverInfo']['name']);
    }

    public function testUnknownProtocolVersionFallsBackToLatest(): void
    {
        $response = $this->server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '1999-01-01']]);

        $this->assertSame(McpServer::SUPPORTED_PROTOCOL_VERSIONS[0], $response['result']['protocolVersion']);
    }

    public function testNotificationsGetNoResponse(): void
    {
        $this->assertNull($this->server->handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']));
    }

    public function testUnknownMethod(): void
    {
        $this->assertSame(-32601, $this->server->handle(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'foo'])['error']['code']);
    }

    public function testToolsList(): void
    {
        $tools = array_column($this->server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])['result']['tools'], 'name');

        $this->assertSame(['list_entries', 'get_entry', 'list_requests', 'get_last_exception', 'clear_entries'], $tools);
    }

    public function testListEntriesAndGetEntry(): void
    {
        illuminar(['answer' => 42])->tag('mcp');
        illuminar()->trackQueries();
        DB::select('select 1');

        $list = $this->callTool('list_entries', ['types' => ['dump']]);

        $this->assertSame(1, $list['total']);
        $this->assertStringContainsString('[mcp]', $list['entries'][0]['summary']);
        $this->assertStringContainsString('answer', $list['entries'][0]['summary']);

        $entry = $this->callTool('get_entry', ['uuid' => $list['entries'][0]['uuid'], 'include_context' => true]);

        // HTML dumps are converted to plain text for agents
        $this->assertStringNotContainsString('<pre', json_encode($entry));
        $this->assertStringContainsString('42', $entry['entry']['data'][0]);
        $this->assertCount(2, $entry['context_entries']);
    }

    public function testGetLastException(): void
    {
        illuminar()->trackExceptions();
        illuminar('before the exception');
        report(new RuntimeException('Payment failed'));

        $result = $this->callTool('get_last_exception');

        $this->assertSame('Payment failed', $result['exception']['message']);
        $this->assertNotEmpty($result['exception']['snippet']);
        $this->assertCount(2, $result['context_entries']);
    }

    public function testClearEntries(): void
    {
        illuminar('test');

        $this->callTool('clear_entries');

        $this->assertEmpty($this->entries());
    }

    public function testUnknownToolIsError(): void
    {
        $response = $this->server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'nope']]);

        $this->assertTrue($response['result']['isError']);
    }

    private function callTool(string $tool, array $arguments = []): array
    {
        $response = $this->server->handle([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'tools/call',
            'params'  => ['name' => $tool, 'arguments' => $arguments],
        ]);

        $this->assertFalse($response['result']['isError']);

        return json_decode($response['result']['content'][0]['text'], true);
    }
}
