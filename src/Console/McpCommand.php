<?php

namespace Adobrovolsky97\Illuminar\Console;

use Adobrovolsky97\Illuminar\Mcp\McpServer;
use Illuminate\Console\Command;

/**
 * php artisan illuminar:mcp
 *
 * MCP server over stdio. Register it in your AI agent, e.g. Claude Code:
 *   claude mcp add illuminar -- php artisan illuminar:mcp
 */
class McpCommand extends Command
{
    protected $signature = 'illuminar:mcp';

    protected $description = 'Start Illuminar MCP server (stdio) for AI agents';

    public function handle(McpServer $server): int
    {
        // stdout is the protocol channel: anything else must go to stderr
        ini_set('display_errors', 'stderr');

        $input = fopen('php://stdin', 'rb');
        $output = fopen('php://stdout', 'wb');

        while (($line = fgets($input)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $message = json_decode($line, true);

            if (!is_array($message)) {
                $this->write($output, ['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']]);
                continue;
            }

            // Batches are not used by MCP clients anymore, but are cheap to support
            $messages = array_is_list($message) ? $message : [$message];

            foreach ($messages as $item) {
                $response = is_array($item) ? $server->handle($item) : null;

                if ($response !== null) {
                    $this->write($output, $response);
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param resource $output
     */
    private function write($output, array $response): void
    {
        fwrite($output, json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n");
        fflush($output);
    }
}
