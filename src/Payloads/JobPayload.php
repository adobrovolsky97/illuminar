<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Illuminate\Queue\Events\JobFailed;

/**
 * Class JobPayload
 */
class JobPayload extends Payload
{
    /**
     * Job Connection
     *
     * @var string
     */
    protected string $connection;

    /**
     * Job Queue
     *
     * @var string|null
     */
    protected ?string $queue;

    /**
     * Job payload
     *
     * @var array
     */
    protected array $payload;

    /**
     * For job watcher we need to remove vendor path from backtrace to show original caller.
     *
     * @var array
     */
    protected array $callerPathsToRemove = ['vendor'];

    /**
     * @param string $connection
     * @param string|null $queue
     * @param array $payload
     */
    public function __construct(string $connection, ?string $queue, array $payload)
    {
        $this->connection = $connection;
        $this->queue = $queue;
        $this->payload = $payload;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $jobData = !isset($this->payload['data']['command'])
            ? ($this->payload['data'] ?? [])
            : ($this->payload['data']['command'] ?? []);

        return [
            'type'       => JobWatcher::getName(),
            'job_class'  => $this->getJobClass(),
            'uuid'       => $this->getUuid(),
            'caller'     => $this->getCaller(),
            'status'     => 'pending',
            'connection' => $this->connection,
            'queue'      => $this->queue,
            'tries'      => $this->payload['maxTries'] ?? 1,
            'timeout'    => $this->payload['timeout'] ?? 10,
            'job'        => app(PrimitiveArgumentFormatter::class)->convertToPrimitive($jobData),
            'exception'  => null,
            'time'       => now()->format('H:i:s')
        ];
    }

    /**
     * Get data for event
     *
     * @param object $event
     * @return array
     */
    public static function fromEvent(object $event): array
    {
        return [
            'type'   => JobWatcher::getName(),
            'status' => $event instanceof JobFailed ? 'failed' : 'processed',
            'exception' => property_exists($event, 'exception')
                ? app(PrimitiveArgumentFormatter::class)->convertToPrimitive($event->exception)
                : null,
            'time'   => now()->format('H:i:s')
        ];
    }

    /**
     * @return string
     */
    public function getJobClass(): string
    {
        return $this->payload['displayName'] ?? 'unknown';
    }
}
