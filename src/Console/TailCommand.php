<?php

namespace Adobrovolsky97\Illuminar\Console;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\Support\EntryText;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * php artisan illuminar:tail --type=query --type=exception --search=users
 *
 * Streams new entries to the terminal - handy for API / CLI development without opening the UI.
 */
class TailCommand extends Command
{
    protected $signature = 'illuminar:tail
        {--type=* : Show only given types (dump, query, model, job, event, cache, mail, http_request, exception, log, request)}
        {--search= : Show only entries matching the string}
        {--json : Print raw JSON entries (one per line), useful for scripts and AI agents}
        {--interval=1 : Polling interval in seconds}
        {--once : Print latest entries and exit}';

    protected $description = 'Stream Illuminar entries to the terminal';

    /**
     * @var array<string, string> uuid => content hash of already printed entries
     */
    private array $printed = [];

    public function handle(SearchHelper $searchHelper): int
    {
        $filters = [
            'types'     => $this->option('type') ?: SearchHelper::TYPES,
            'search'    => $this->option('search'),
            'page_size' => 500,
        ];

        $first = true;

        do {
            $entries = array_reverse($searchHelper->filterData(StorageDriverFactory::getDriverForConfig()->getData(), $filters)->items());

            foreach ($entries as $entry) {
                $hash = md5((string)json_encode($entry));

                if (($this->printed[$entry['uuid']] ?? null) === $hash) {
                    continue;
                }

                // Do not flood the terminal with history on start
                if ($first && !$this->option('once')) {
                    $this->printed[$entry['uuid']] = $hash;
                    continue;
                }

                $this->printed[$entry['uuid']] = $hash;
                $this->printEntry($entry);
            }

            if ($first && !$this->option('once') && !$this->option('json')) {
                $this->components->info('Waiting for new Illuminar entries... (Ctrl+C to stop)');
            }

            $first = false;

            if (!$this->option('once')) {
                usleep((int)(max(0.2, (float)$this->option('interval')) * 1000000));
            }
        } while (!$this->option('once'));

        return self::SUCCESS;
    }

    private function printEntry(array $entry): void
    {
        if ($this->option('json')) {
            $this->line((string)json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

            return;
        }

        $time = $this->formatTime($entry['time'] ?? null);
        $type = str_pad($entry['type'], 12);

        $this->line(sprintf(
            '<fg=gray>%s</> <fg=%s>%s</> %s',
            $time,
            $this->color($entry),
            $type,
            Str::limit(preg_replace('/\s+/', ' ', EntryText::summary($entry)), 300)
        ));

        if (!empty($entry['caller'])) {
            $this->line(str_repeat(' ', 22) . '<fg=gray>' . $entry['caller'] . '</>');
        }
    }

    private function color(array $entry): string
    {
        if ($entry['type'] === 'exception' || ($entry['status'] ?? null) === 'failed' || !empty($entry['is_slow'])
            || !empty($entry['n_plus_one']) || (int)($entry['status_code'] ?? 0) >= 500
            || in_array($entry['level'] ?? null, ['error', 'critical', 'alert', 'emergency'], true)) {
            return 'red';
        }

        return [
            'dump'         => 'cyan',
            'query'        => 'blue',
            'model'        => 'yellow',
            'job'          => 'magenta',
            'http_request' => 'green',
            'request'      => 'bright-white',
            'log'          => 'yellow',
        ][$entry['type']] ?? 'white';
    }

    private function formatTime(?string $time): string
    {
        try {
            return $time ? Carbon::parse($time)->setTimezone(config('app.timezone', 'UTC'))->format('H:i:s.v') : str_repeat(' ', 12);
        } catch (Throwable $e) {
            return str_pad((string)$time, 12);
        }
    }
}
