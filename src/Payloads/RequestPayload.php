<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Support\RequestProfile;
use Adobrovolsky97\Illuminar\Watchers\RequestWatcher;

/**
 * Summary of a request / command / job
 */
class RequestPayload extends Payload
{
    private RequestProfile $profile;

    private string $kind;

    private array $details;

    /**
     * @param string $kind http, command or job
     * @param array $details kind specific info: method, url, status etc.
     */
    public function __construct(RequestProfile $profile, string $kind, array $details = [])
    {
        $this->profile = $profile;
        $this->kind = $kind;
        $this->details = $details;

        parent::__construct();
    }

    public function toArray(): array
    {
        $profile = $this->profile;
        $repeated = $profile->repeatedQueries(max(2, (int)config('illuminar.requests.n_plus_one_threshold', 3)));

        return array_merge($this->baseFields(RequestWatcher::getName()), $this->details, [
            // The summary belongs to the profiled context even if it is written after the context was left
            'context'          => $profile->label,
            'context_id'       => $profile->contextId,
            'caller'           => '',
            'kind'             => $this->kind,
            'duration'         => $profile->durationMs(),
            'memory'           => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
            'queries_count'    => $profile->queries,
            'queries_time'     => round($profile->queriesTime, 2),
            'slow_queries'     => $profile->slowQueries,
            'n_plus_one'       => $repeated,
            'stats'            => array_filter([
                'cache hits'        => $profile->cacheHits,
                'cache misses'      => $profile->cacheMisses,
                'cache writes'      => $profile->cacheWrites,
                'http requests'     => $profile->httpRequests,
                'jobs dispatched'   => $profile->jobsDispatched,
                'mails'             => $profile->mails,
                'logs'              => $profile->logs,
                'exceptions'        => $profile->exceptions,
            ]),
        ]);
    }
}
