<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Throwable;

/**
 * Class JobPayload
 */
class JobPayload extends Payload
{
    protected string $connection;

    protected ?string $queue;

    protected array $payload;

    /**
     * For job watcher we need to remove vendor path from backtrace to show original caller.
     */
    protected array $callerPathsToRemove = ['vendor/'];

    public function __construct(string $connection, ?string $queue, array $payload)
    {
        $this->connection = $connection;
        $this->queue = $queue;
        $this->payload = $payload;

        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->baseFields(JobWatcher::getName()), [
            'job_class'  => $this->getJobClass(),
            'status'     => 'pending',
            'connection' => $this->connection,
            'queue'      => $this->queue,
            'tries'      => $this->payload['maxTries'] ?? null,
            'timeout'    => $this->payload['timeout'] ?? null,
            'job'        => $this->format($this->getJobData()),
            'exception'  => null,
        ]);
    }

    /**
     * Partial update of the stored job entry for JobProcessing / JobProcessed / JobFailed events
     */
    public static function fromEvent(object $event, ?float $durationMs = null): array
    {
        if ($event instanceof JobProcessing) {
            $status = 'processing';
        } elseif ($event instanceof JobFailed || $event instanceof JobExceptionOccurred || self::jobHasFailed($event)) {
            $status = 'failed';
        } else {
            $status = 'processed';
        }

        // No "type" here: a partial update must not create an entry if the original one is gone
        $data = [
            'status'   => $status,
            'attempts' => self::attempts($event),
        ];

        if ($durationMs !== null) {
            $data['duration'] = round($durationMs, 2);
        }

        if (($event->exception ?? null) instanceof Throwable) {
            $exception = $event->exception;

            $data['exception'] = [
                'class'   => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile() . ':' . $exception->getLine(),
            ];
        }

        return $data;
    }

    public function getJobClass(): string
    {
        return $this->payload['displayName'] ?? 'unknown';
    }

    /**
     * Job properties for display.
     *
     * The queued command is serialized; it is unserialized WITHOUT instantiating classes
     * (no __wakeup, no models fetched from DB) - properties are still visible in the dump.
     *
     * @return mixed
     */
    private function getJobData()
    {
        $data = $this->payload['data'] ?? [];

        if (!isset($data['command']) || !is_string($data['command'])) {
            return $data;
        }

        if (!preg_match('/^O:\d+:"/', $data['command'])) {
            return ['commandName' => $data['commandName'] ?? null, 'command' => '[encrypted]'];
        }

        try {
            return unserialize($data['command'], ['allowed_classes' => false]);
        } catch (Throwable $e) {
            return $data;
        }
    }

    private static function jobHasFailed(object $event): bool
    {
        try {
            return isset($event->job) && method_exists($event->job, 'hasFailed') && $event->job->hasFailed();
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function attempts(object $event): ?int
    {
        try {
            return isset($event->job) ? $event->job->attempts() : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
