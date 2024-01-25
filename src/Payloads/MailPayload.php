<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

/**
 * Class MailPayload
 */
class MailPayload extends Payload
{
    /**
     * @var MessageSent
     */
    private MessageSent $event;

    /**
     * @var array
     */
    protected array $callerPathsToRemove = ['vendor'];

    /**
     * @param MessageSent $event
     */
    public function __construct(MessageSent $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $mailable = $this->getMailable($this->event);

        return [
            'uuid'           => $this->getUuid(),
            'type'           => MailWatcher::getName(),
            'mailable_class' => $mailable,
            'subject'        => $this->event->message->getSubject(),
            'queued'         => $this->getQueuedStatus($this->event),
            'from'           => $this->formatAddresses($this->event->message->getFrom()),
            'to'             => $this->formatAddresses($this->event->message->getTo()),
            'cc'             => $this->formatAddresses($this->event->message->getCc()),
            'bcc'            => $this->formatAddresses($this->event->message->getBcc()),
            'html'           => $this->event->message->getHtmlBody() ?? $this->event->message->getTextBody() ?? '',
            'time'           => now()->format('H:i:s'),
            'caller'         => $this->getCaller(),
        ];
    }

    /**
     * Get mailable
     *
     * @param $event
     * @return string|null
     */
    private function getMailable($event): ?string
    {
        return $event->data['__laravel_notification'] ?? $event->data['__illuminar_mailable'] ?? null;
    }

    /**
     *  Get queued status
     *
     * @param $event
     * @return bool
     */
    private function getQueuedStatus($event): bool
    {
        return $event->data['__laravel_notification_queued'] ?? $event->data['__illuminar_queued'] ?? false;
    }

    /**
     * Format addresses
     *
     * @param array|null $addresses
     * @return array|null
     */
    private function formatAddresses(?array $addresses): ?array
    {
        if (is_null($addresses)) {
            return null;
        }

        return collect($addresses)
            ->flatMap(function ($address, $key) {
                if ($address instanceof Address) {
                    return [$address->getAddress() => $address->getName()];
                }
                return [$key => $address];
            })
            ->all();
    }
}
