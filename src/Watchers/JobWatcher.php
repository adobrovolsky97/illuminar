<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Payloads\JobPayload;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

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
        Queue::createPayloadUsing(function () {
            return ['illuminar_uuid' => $this->enabled ? Str::orderedUuid()->toString() : null];
        });

        Event::listen(
            [
                JobQueued::class,
                JobProcessing::class,
                JobProcessed::class,
                JobFailed::class
            ],
            function ($event) {

                $jobPayload = new JobPayload($event);

                if (in_array($jobPayload->getJobClass(), config('illuminar.jobs.ignored_jobs', []))) {
                    return;
                }

                // If illuminar_uuid is null, it means that job tracking is disabled
                if ($jobPayload->getIlluminarUuid() === null) {
                    return;
                }

                DataCollector::addToBatch($jobPayload);

                if ($event instanceof JobQueued || config('queue.default') === 'sync') {
                    return;
                }

                // Append data to the batch should be called in case of queued processing
                DataCollector::appendData();
            }
        );
    }
}
