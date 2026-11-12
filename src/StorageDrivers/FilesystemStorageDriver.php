<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Append-only JSON Lines storage.
 *
 * Every entry (or a partial update of an entry, e.g. a dump tag or a job status) is appended
 * as a single line under an exclusive lock, so writes are O(1) and safe for concurrent
 * processes (web requests, queue workers, artisan commands).
 * Lines with the same uuid are merged on read. The file is compacted when it grows too big.
 */
class FilesystemStorageDriver implements StorageDriverInterface
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_INVALID_UTF8_SUBSTITUTE
    | JSON_PARTIAL_OUTPUT_ON_ERROR;

    private Filesystem $filesystem;

    private string $directory;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->directory = rtrim((string)config('illuminar.storage.path', storage_path('illuminar')), '/\\') . DIRECTORY_SEPARATOR;
    }

    public function clear(): void
    {
        $handle = $this->open('c');

        if ($handle === null) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        // Remove storage file of Illuminar v1 if it is still there
        $legacyFile = $this->directory . config('illuminar.storage.filename', 'illuminar') . '.json';
        if ($this->filesystem->exists($legacyFile)) {
            $this->filesystem->delete($legacyFile);
        }
    }

    public function saveEntry(array $data): void
    {
        if (!isset($data['uuid'])) {
            return;
        }

        $line = json_encode($data, self::JSON_FLAGS);

        if ($line === false) {
            return;
        }

        $this->ensureDirectoryExists();

        $handle = $this->open('a');

        if ($handle === null) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            fwrite($handle, $line . "\n");
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $this->compactIfNeeded();
    }

    public function getData(): array
    {
        if (!is_file($this->getFilePath())) {
            return [];
        }

        $handle = $this->open('r');

        if ($handle === null) {
            return [];
        }

        try {
            flock($handle, LOCK_SH);
            $content = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $entries = array_reverse($this->parse((string)$content));

        return array_slice($entries, 0, $this->limit());
    }

    /**
     * Parse JSON lines and merge partial updates into entries. Returns entries oldest first.
     */
    private function parse(string $content): array
    {
        $entries = [];

        foreach (explode("\n", $content) as $line) {
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);

            if (!is_array($row) || !isset($row['uuid'])) {
                continue;
            }

            $uuid = $row['uuid'];
            $entries[$uuid] = isset($entries[$uuid]) ? array_merge($entries[$uuid], $row) : $row;
        }

        // Partial updates whose original entry was already removed by compaction are dropped
        return array_values(array_filter($entries, function (array $entry) {
            return isset($entry['type']);
        }));
    }

    /**
     * Rewrite storage file keeping only latest entries.
     */
    private function compactIfNeeded(): void
    {
        $path = $this->getFilePath();
        $maxSize = $this->maxFileSize();

        clearstatcache(true, $path);

        if (!is_file($path) || filesize($path) <= $maxSize) {
            return;
        }

        $handle = $this->open('c+');

        if ($handle === null) {
            return;
        }

        try {
            flock($handle, LOCK_EX);

            clearstatcache(true, $path);
            // Another process could compact the file while we were waiting for the lock
            if (filesize($path) <= $maxSize) {
                return;
            }

            $entries = array_reverse($this->parse((string)stream_get_contents($handle)));

            // Keep the latest entries until the limit or half of the max size is reached,
            // so compaction does not happen on every write
            $lines = [];
            $size = 0;
            foreach ($entries as $entry) {
                $line = json_encode($entry, self::JSON_FLAGS) . "\n";
                $size += strlen($line);

                if (count($lines) >= $this->limit() || ($size > $maxSize / 2 && !empty($lines))) {
                    break;
                }

                $lines[] = $line;
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, implode('', array_reverse($lines)));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return resource|null
     */
    private function open(string $mode)
    {
        $handle = @fopen($this->getFilePath(), $mode . 'b');

        return $handle === false ? null : $handle;
    }

    private function getFilePath(): string
    {
        return $this->directory . config('illuminar.storage.filename', 'illuminar') . '.jsonl';
    }

    private function limit(): int
    {
        return max(1, (int)config('illuminar.storage.limit', 2000));
    }

    private function maxFileSize(): int
    {
        return (int)(max(0.001, (float)config('illuminar.storage.max_file_size', 20)) * 1024 * 1024);
    }

    private function ensureDirectoryExists(): void
    {
        if ($this->filesystem->isDirectory($this->directory)) {
            return;
        }

        if (!$this->filesystem->makeDirectory($this->directory, 0755, true, true) && !$this->filesystem->isDirectory($this->directory)) {
            throw new RuntimeException("Cannot create directory '$this->directory'.");
        }

        $this->filesystem->put($this->directory . '.gitignore', "*\n!.gitignore\n");
    }
}
