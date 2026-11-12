<?php

namespace Adobrovolsky97\Illuminar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Adobrovolsky97\Illuminar\Support\PendingDump dump(mixed ...$args)
 * @method static void dd(mixed ...$args)
 * @method static \Adobrovolsky97\Illuminar\Support\PendingDump showEnv(bool $maskSensitive = true)
 * @method static \Adobrovolsky97\Illuminar\Illuminar mailable(\Illuminate\Contracts\Support\Renderable $mailable)
 * @method static mixed watch(callable $callback, array|null $types = null)
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackAll()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingAll()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackQueries()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingQueries()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackSlowQueries()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingSlowQueries()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackExceptions()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingExceptions()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackModels()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingModels()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackJobs()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingJobs()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackEvents()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingEvents()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackCaches()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingCaches()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackMails()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingMails()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackHttpRequests()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingHttpRequests()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackLogs()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingLogs()
 * @method static \Adobrovolsky97\Illuminar\Illuminar trackRequests()
 * @method static \Adobrovolsky97\Illuminar\Illuminar stopTrackingRequests()
 * @method static \Adobrovolsky97\Illuminar\Illuminar clear()
 *
 * @see \Adobrovolsky97\Illuminar\Illuminar
 */
class Illuminar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Adobrovolsky97\Illuminar\Illuminar::class;
    }
}
