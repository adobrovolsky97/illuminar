<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Payloads\JobPayload;
use Adobrovolsky97\Illuminar\Support\Context;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Class JobWatcher
 */
class JobWatcher extends Watcher
{
    /**
     * Start time of jobs being processed by the current process
     *
     * @var array<string, float>
     */
    private array $startedAt = [];

    /**
     * Job (object id) which execution context is currently active
     */
    private ?int $contextJobId = null;

    public static function getName(): string
    {
        return 'job';
    }

    protected function initialize(): void
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $uuid = $this->handleIncomingJob((string)$connection, $queue, (array)$payload);

            return $uuid ? ['illuminar_uuid' => $uuid] : [];
        });

        // Processing results are tracked even if the watcher is disabled in the current process,
        // as the job could be dispatched (and tracked) by another one, e.g. web request -> worker
        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $this->leaveJobContext();
            Context::push('job ' . $this->getJobName($event));
            $this->contextJobId = spl_object_id($event->job);

            $uuid = $this->getIlluminarUuid($event);

            if ($uuid === null) {
                return;
            }

            $this->startedAt[$uuid] = microtime(true);

            $this->record(function () use ($uuid, $event) {
                return ['uuid' => $uuid] + JobPayload::fromEvent($event);
            });
        });

        Event::listen([JobProcessed::class, JobFailed::class, JobExceptionOccurred::class], function (object $event) {
            $this->handleProcessedJobResult($event);
        });
    }

    /**
     * Store dispatched job, returns its illuminar uuid.
     */
    protected function handleIncomingJob(string $connection, ?string $queue, array $payload): ?string
    {
        if (!$this->enabled || Illuminar::isRecording()) {
            return null;
        }

        $jobPayload = new JobPayload($connection, $queue, $payload);

        if (in_array($jobPayload->getJobClass(), (array)config('illuminar.jobs.ignored_jobs', []), true)) {
            return null;
        }

        $stored = false;

        $this->record(function () use ($jobPayload, &$stored) {
            $stored = true;

            return $jobPayload;
        });

        return $stored ? $jobPayload->getUuid() : null;
    }

    protected function handleProcessedJobResult(object $event): void
    {
        $uuid = $this->getIlluminarUuid($event);

        if ($uuid !== null) {
            $duration = isset($this->startedAt[$uuid]) ? (microtime(true) - $this->startedAt[$uuid]) * 1000 : null;
            unset($this->startedAt[$uuid]);

            $this->record(function () use ($uuid, $event, $duration) {
                return ['uuid' => $uuid] + JobPayload::fromEvent($event, $duration);
            });
        }

        if (isset($event->job) && $this->contextJobId === spl_object_id($event->job)) {
            $this->leaveJobContext();
        }
    }

    private function leaveJobContext(): void
    {
        if ($this->contextJobId !== null) {
            Context::pop();
            $this->contextJobId = null;
        }
    }

    private function getIlluminarUuid(object $event): ?string
    {
        try {
            return $event->job->payload()['illuminar_uuid'] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getJobName(object $event): string
    {
        try {
            return $event->job->resolveName();
        } catch (Throwable $e) {
            return 'unknown';
        }
    }

}
