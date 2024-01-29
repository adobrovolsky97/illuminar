<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Throwable;

/**
 * Class JobPayload
 */
class JobPayload extends Payload
{
    /**
     * Event object
     *
     * @var object
     */
    private object $event;

    /**
     * Job object
     *
     * @var object
     */
    private object $job;

    /**
     * Exception object
     *
     * @var Throwable|null
     */
    private ?Throwable $exception = null;

    /**
     * For job watcher we need to remove vendor path from backtrace to show original caller.
     *
     * @var array
     */
    protected array $callerPathsToRemove = ['vendor'];

    /**
     * @param object $event
     */
    public function __construct(object $event)
    {
        $this->event = $event;

        $this->job = $event->job instanceof Job
            ? unserialize($event->job->payload()['data']['command'])
            : $event->job;

        if (property_exists($event, 'exception')) {
            $this->exception = $event->exception ?? null;
        }

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $argumentFormatter = app(PrimitiveArgumentFormatter::class);
        return [
            'type'      => JobWatcher::getName(),
            'job_class' => get_class($this->job),
            'uuid'      => $this->getUuid(),
            'caller'    => $this->event instanceof JobQueued ? $this->getCaller() : null,
            'status'    => $this->getStatusFromEvent(),
            'queue'     => $this->job->queue ?? 'default',
            'job'       => $this->job ? $argumentFormatter->convertToPrimitive($this->job) : null,
            'exception' => $this->exception ? $argumentFormatter->convertToPrimitive($this->exception) : null,
            'time'      => now()->format('H:i:s')
        ];
    }

    /**
     * Get uuid from event or generate new one.
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->getIlluminarUuid() ?? $this->uuid;
    }

    /**
     * Extract uuid from job.
     *
     * @return string|null
     */
    public function getIlluminarUuid(): ?string
    {
        switch (true) {
            case $this->event instanceof JobQueued:
                return $this->event->payload()['illuminar_uuid'] ?? null;
            case $this->event instanceof JobProcessing:
            case $this->event instanceof JobProcessed:
            case $this->event instanceof JobFailed:
                return $this->event->job->payload()['illuminar_uuid'] ?? null;
            default:
                return null;
        }
    }

    /**
     * @return string
     */
    public function getJobClass(): string
    {
        return get_class($this->job);
    }

    /**
     * Get status from event.
     *
     * @return string
     */
    private function getStatusFromEvent(): string
    {
        switch (true) {
            case $this->event instanceof JobQueued:
                return 'queued';
            case $this->event instanceof JobProcessing:
                return 'processing';
            case $this->event instanceof JobProcessed:
                return 'processed';
            case $this->event instanceof JobFailed:
                return 'failed';
            default:
                return 'unknown';
        }
    }
}
