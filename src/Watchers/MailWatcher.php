<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\MailablePreviewPayload;
use Adobrovolsky97\Illuminar\Payloads\MailPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Event;

/**
 * Class MailWatcher
 */
class MailWatcher extends Watcher
{
    /**
     * Mailable::buildViewDataUsing() is global, it must be wrapped only once per process
     */
    private static bool $viewDataCallbackRegistered = false;

    public static function getName(): string
    {
        return 'mail';
    }

    protected function initialize(): void
    {
        $this->registerViewDataCallback();

        Event::listen(MessageSent::class, function (MessageSent $event) {
            if (!$this->enabled) {
                return;
            }

            $this->record(function () use ($event) {
                return new MailPayload($event);
            });
        });
    }

    /**
     * Store mailable / mail message preview
     */
    public function mailable(Renderable $mailable): void
    {
        $this->record(function () use ($mailable) {
            return new MailablePreviewPayload($mailable);
        });
    }

    private function registerViewDataCallback(): void
    {
        if (self::$viewDataCallbackRegistered || !property_exists(Mailable::class, 'viewDataCallback')) {
            return;
        }

        self::$viewDataCallbackRegistered = true;

        $existingCallback = Mailable::$viewDataCallback;

        Mailable::buildViewDataUsing(function ($mailable) use ($existingCallback) {
            $existingData = $existingCallback ? (array)call_user_func($existingCallback, $mailable) : [];

            return array_merge($existingData, [
                '__illuminar_mailable' => get_class($mailable),
                '__illuminar_queued'   => $mailable instanceof ShouldQueue,
            ]);
        });
    }
}
