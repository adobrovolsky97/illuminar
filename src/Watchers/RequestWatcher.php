<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Payloads\RequestPayload;
use Adobrovolsky97\Illuminar\Support\Context;
use Adobrovolsky97\Illuminar\Support\RequestProfile;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes a summary (profile) for every HTTP request, artisan command and queued job:
 * duration, memory, number of queries, repeated queries (N+1) with the place in code, cache, http calls etc.
 */
class RequestWatcher extends Watcher
{
    /**
     * @var array<string, RequestProfile> context id => profile
     */
    private array $profiles = [];

    /**
     * Job object id => context id of the job execution
     *
     * @var array<int, string>
     */
    private array $jobContexts = [];

    private int $commandDepth = 0;

    public static function getName(): string
    {
        return 'request';
    }

    protected function initialize(): void
    {
        $this->listenForStats();
        $this->listenForBoundaries();
    }

    private function listenForStats(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            $this->collect(function (RequestProfile $profile) use ($event) {
                $profile->addQuery($event, QueryWatcher::isSlow($event));
            });
        });

        Event::listen([CacheHit::class, CacheMissed::class, KeyWritten::class], function (object $event) {
            $this->collect(function (RequestProfile $profile) use ($event) {
                if ($event instanceof CacheHit) {
                    $profile->cacheHits++;
                } elseif ($event instanceof CacheMissed) {
                    $profile->cacheMisses++;
                } else {
                    $profile->cacheWrites++;
                }
            });
        });

        Event::listen([ResponseReceived::class, ConnectionFailed::class], function () {
            $this->collect(function (RequestProfile $profile) {
                $profile->httpRequests++;
            });
        });

        Event::listen(MessageSent::class, function () {
            $this->collect(function (RequestProfile $profile) {
                $profile->mails++;
            });
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $this->collect(function (RequestProfile $profile) use ($event) {
                if (($event->context['exception'] ?? null) instanceof Throwable) {
                    $profile->exceptions++;
                } else {
                    $profile->logs++;
                }
            });
        });

        Queue::createPayloadUsing(function () {
            $this->collect(function (RequestProfile $profile) {
                $profile->jobsDispatched++;
            });

            return [];
        });
    }

    private function listenForBoundaries(): void
    {
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $this->flushHttpRequest($event);
        });

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($this->commandDepth++ === 0 && $this->enabled) {
                $context = Context::current();
                $this->profiles[$context['id']] = new RequestProfile($context['id'], $context['label']);
            }
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if (--$this->commandDepth > 0) {
                return;
            }

            $this->commandDepth = 0;
            $profile = $this->profiles[Context::current()['id']] ?? null;
            unset($this->profiles[Context::current()['id']]);

            if ($profile === null || !$this->enabled || $this->isIgnoredCommand((string)$event->command)) {
                return;
            }

            $this->store($profile, 'command', [
                'command'   => $event->command,
                'exit_code' => $event->exitCode,
            ]);
        });

        // JobWatcher (always registered before this watcher) has already entered the job context
        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            if (!$this->enabled) {
                return;
            }

            $context = Context::current();
            $this->jobContexts[spl_object_id($event->job)] = $context['id'];
            $this->profiles[$context['id']] = new RequestProfile($context['id'], $context['label']);
        });

        Event::listen([JobProcessed::class, JobFailed::class], function (object $event) {
            $jobId = spl_object_id($event->job);
            $contextId = $this->jobContexts[$jobId] ?? null;
            unset($this->jobContexts[$jobId]);

            $profile = $contextId !== null ? ($this->profiles[$contextId] ?? null) : null;
            unset($this->profiles[$contextId]);

            if ($profile === null || !$this->enabled) {
                return;
            }

            $this->store($profile, 'job', [
                'job_class'  => $this->safe(function () use ($event) {
                    return $event->job->resolveName();
                }),
                'status'     => $event instanceof JobFailed || $this->safe(function () use ($event) {
                    return $event->job->hasFailed();
                }) ? 'failed' : 'processed',
                'queue'      => $this->safe(function () use ($event) {
                    return $event->job->getQueue();
                }),
                'connection' => $event->connectionName ?? null,
            ]);
        });
    }

    private function flushHttpRequest(RequestHandled $event): void
    {
        $context = Context::current();
        $profile = $this->profiles[$context['id']] ?? null;
        unset($this->profiles[$context['id']]);

        if (!$this->enabled || $this->isIgnoredPath($event->request->path())) {
            return;
        }

        $startedAt = defined('LARAVEL_START') ? LARAVEL_START : ($event->request->server('REQUEST_TIME_FLOAT') ?: null);
        $profile ??= new RequestProfile($context['id'], $context['label']);

        if ($startedAt) {
            $profile->startedAt = (float)$startedAt;
        }

        $route = $event->request->route();

        $this->store($profile, 'http', [
            'method'      => $event->request->method(),
            'url'         => $event->request->fullUrl(),
            'status_code' => $event->response->getStatusCode(),
            'route'       => $route ? ($route->getName() ?: $route->getActionName()) : null,
        ]);
    }

    /**
     * Add statistics to the profile of the current context
     */
    private function collect(callable $callback): void
    {
        if (!$this->enabled || Illuminar::isRecording()) {
            return;
        }

        try {
            $context = Context::current();
            $profile = $this->profiles[$context['id']] ??= new RequestProfile($context['id'], $context['label']);

            $callback($profile);
        } catch (Throwable $exception) {
            if (Illuminar::$throwInternalErrors) {
                throw $exception;
            }
        }
    }

    private function store(RequestProfile $profile, string $kind, array $details): void
    {
        $this->record(function () use ($profile, $kind, $details) {
            return new RequestPayload($profile, $kind, $details);
        });
    }

    private function isIgnoredPath(string $path): bool
    {
        $path = trim($path, '/');
        $illuminarPath = trim((string)config('illuminar.path', 'illuminar'), '/');

        if ($path === $illuminarPath || str_starts_with($path, $illuminarPath . '/')) {
            return true;
        }

        return Str::is((array)config('illuminar.requests.ignored_paths', []), $path);
    }

    private function isIgnoredCommand(string $command): bool
    {
        return $command === '' || Str::is((array)config('illuminar.requests.ignored_commands', []), $command);
    }

    /**
     * @return mixed
     */
    private function safe(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            return null;
        }
    }
}
