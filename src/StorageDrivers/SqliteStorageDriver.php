<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

use Illuminate\Filesystem\Filesystem;
use PDO;
use RuntimeException;
use Throwable;

/**
 * SQLite storage: indexed by type and context, WAL mode for concurrent web / worker / console processes.
 *
 * Uses its own PDO connection to a separate file, so it never touches application databases,
 * transactions or the query log (Illuminar's own queries are not tracked).
 */
class SqliteStorageDriver implements StorageDriverInterface, FiltersEntries
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_INVALID_UTF8_SUBSTITUTE
    | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Old entries are pruned every N inserts
     */
    private const PRUNE_EVERY = 50;

    private Filesystem $filesystem;

    private ?PDO $pdo = null;

    private string $path;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        $directory = rtrim((string)config('illuminar.storage.path', storage_path('illuminar')), '/\\');
        $this->path = $directory . DIRECTORY_SEPARATOR . config('illuminar.storage.filename', 'illuminar') . '.sqlite';
    }

    public static function isSupported(): bool
    {
        return extension_loaded('pdo_sqlite');
    }

    public function clear(): void
    {
        if (!is_file($this->path)) {
            return;
        }

        $this->connection()->exec('DELETE FROM entries');
    }

    public function saveEntry(array $data): void
    {
        if (!isset($data['uuid'])) {
            return;
        }

        $pdo = $this->connection();

        $pdo->exec('BEGIN IMMEDIATE');

        try {
            $select = $pdo->prepare('SELECT data FROM entries WHERE uuid = ?');
            $select->execute([$data['uuid']]);
            $existing = $select->fetchColumn();

            if ($existing !== false) {
                // Partial update (dump tag, job status...) is merged into the stored entry
                $data = array_merge((array)json_decode((string)$existing, true), $data);

                $pdo->prepare('UPDATE entries SET type = ?, context_id = ?, data = ? WHERE uuid = ?')
                    ->execute([$data['type'] ?? null, $data['context_id'] ?? null, $this->encode($data), $data['uuid']]);
            } elseif (isset($data['type'])) {
                $pdo->prepare('INSERT INTO entries (uuid, type, context_id, data) VALUES (?, ?, ?, ?)')
                    ->execute([$data['uuid'], $data['type'], $data['context_id'] ?? null, $this->encode($data)]);

                $this->pruneIfNeeded((int)$pdo->lastInsertId());
            }

            $pdo->exec('COMMIT');
        } catch (Throwable $exception) {
            $pdo->exec('ROLLBACK');

            throw $exception;
        }
    }

    public function getData(): array
    {
        return $this->getFilteredData();
    }

    /**
     * Entries (newest first) narrowed by indexed columns
     */
    public function getFilteredData(array $types = [], ?string $contextId = null, ?int $limit = null): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $where = [];
        $bindings = [];

        if (!empty($types)) {
            $where[] = 'type IN (' . implode(', ', array_fill(0, count($types), '?')) . ')';
            array_push($bindings, ...array_values($types));
        }

        if ($contextId !== null && $contextId !== '') {
            $where[] = 'context_id = ?';
            $bindings[] = $contextId;
        }

        $sql = 'SELECT data FROM entries'
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY id DESC LIMIT ' . (int)($limit ?? $this->limit());

        $statement = $this->connection()->prepare($sql);
        $statement->execute($bindings);

        $entries = [];

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $row) {
            $entry = json_decode((string)$row, true);

            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    public function countByType(?string $contextId = null): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $sql = 'SELECT type, COUNT(*) FROM entries' . ($contextId ? ' WHERE context_id = ?' : '') . ' GROUP BY type';
        $statement = $this->connection()->prepare($sql);
        $statement->execute($contextId ? [$contextId] : []);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    private function pruneIfNeeded(int $lastId): void
    {
        if ($lastId % self::PRUNE_EVERY !== 0) {
            return;
        }

        $this->connection()
            ->prepare('DELETE FROM entries WHERE id <= ?')
            ->execute([$lastId - $this->limit()]);
    }

    private function connection(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        if (!self::isSupported()) {
            throw new RuntimeException('Illuminar sqlite storage requires the pdo_sqlite extension.');
        }

        $this->ensureDirectoryExists(dirname($this->path));

        $pdo = new PDO('sqlite:' . $this->path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec('PRAGMA busy_timeout = 3000');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');

        $pdo->exec('CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT NOT NULL UNIQUE,
            type TEXT,
            context_id TEXT,
            data TEXT NOT NULL
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS entries_type_id ON entries (type, id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS entries_context_id ON entries (context_id)');

        return $this->pdo = $pdo;
    }

    private function encode(array $data): string
    {
        return (string)json_encode($data, self::JSON_FLAGS);
    }

    private function limit(): int
    {
        return max(1, (int)config('illuminar.storage.limit', 2000));
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if ($this->filesystem->isDirectory($directory)) {
            return;
        }

        $this->filesystem->makeDirectory($directory, 0755, true, true);
        $this->filesystem->put($directory . DIRECTORY_SEPARATOR . '.gitignore', "*\n!.gitignore\n");
    }
}
