<?php

namespace Adobrovolsky97\Illuminar\Watchers;

use Adobrovolsky97\Illuminar\Payloads\MailablePreviewPayload;
use Adobrovolsky97\Illuminar\Payloads\MailPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Class MailWatcher
 */
class MailWatcher extends Watcher
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'mail';
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        $existingCallback = Mailable::$viewDataCallback;

        Mailable::buildViewDataUsing(function ($mailable) use ($existingCallback) {
            $existingData = $existingCallback ? call_user_func($existingCallback, $mailable) : [];

            // Adding mailable data to mail payload
            return array_merge($existingData, [
                '__illuminar_mailable' => get_class($mailable),
                '__illuminar_queued'   => in_array(ShouldQueue::class, class_implements($mailable)),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event) {
            if (!$this->enabled) {
                return;
            }

            $this->storageDriver->saveEntry((new MailPayload($event))->toArray());
        });
    }

    /**
     * Show mailable content
     *
     * @param Mailable $mailable
     * @return void
     */
    public function mailable(Mailable $mailable): void
    {
        try {
            $this->storageDriver->saveEntry((new MailablePreviewPayload($mailable))->toArray());
        }catch (Throwable $exception){

        }
    }
}
