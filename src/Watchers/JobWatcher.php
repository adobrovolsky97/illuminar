<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\JobPayload;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;

/**
 * Class JobWatcher
 */
class JobWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'job';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['illuminar_uuid' => optional($this->handleIncomingJob($connection, $queue, $payload))->getUuid()];
        });

        Event::listen([JobProcessed::class, JobFailed::class], function (object $event) {
            $this->handleProcessedJobResult($event);
        });
    }

    /**
     * Handle incoming job.
     *
     * @param string $connection
     * @param string|null $queue
     * @param array $payload
     * @return JobPayload|null
     */
    protected function handleIncomingJob(string $connection, ?string $queue, array $payload): ?JobPayload
    {
        if (!$this->enabled) {
            return null;
        }

        $jobPayload = new JobPayload($connection, $queue, $payload);

        if (in_array($jobPayload->getJobClass(), config('illuminar.jobs.ignored_jobs', []))) {
            return null;
        }

        $this->storageDriver->saveEntry($jobPayload->toArray());

        return $jobPayload;
    }

    /**
     * Handle processed job result.
     *
     * @param object $event
     * @return void
     */
    protected function handleProcessedJobResult(object $event): void
    {
        if (!$event instanceof JobFailed && !$event instanceof JobProcessed) {
            return;
        }

        $illuminarUuid = $event->job->payload()['illuminar_uuid'] ?? null;

        if ($illuminarUuid === null) {
            return;
        }

        $this->storageDriver->saveEntry(array_merge(
            ['uuid' => $illuminarUuid],
            JobPayload::fromEvent($event)
        ));
    }
}
