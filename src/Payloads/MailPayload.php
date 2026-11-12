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
    private MessageSent $event;

    protected array $callerPathsToRemove = ['vendor/'];

    public function __construct(MessageSent $event)
    {
        $this->event = $event;

        parent::__construct();
    }

    public function toArray(): array
    {
        $message = $this->event->message;

        return array_merge($this->baseFields(MailWatcher::getName()), [
            'mailable_class' => $this->getMailable(),
            'subject'        => $message->getSubject(),
            'queued'         => $this->getQueuedStatus(),
            'from'           => $this->formatAddresses($message->getFrom()),
            'to'             => $this->formatAddresses($message->getTo()),
            'cc'             => $this->formatAddresses($message->getCc()),
            'bcc'            => $this->formatAddresses($message->getBcc()),
            'html'           => $this->body($message->getHtmlBody())
                ?? nl2br(e((string)$this->body($message->getTextBody()))),
        ]);
    }

    /**
     * @param string|resource|null $body
     */
    private function body($body): ?string
    {
        if (is_resource($body)) {
            return (string)stream_get_contents($body);
        }

        return $body === null ? null : (string)$body;
    }

    private function getMailable(): ?string
    {
        return $this->event->data['__laravel_notification'] ?? $this->event->data['__illuminar_mailable'] ?? null;
    }

    private function getQueuedStatus(): bool
    {
        return (bool)($this->event->data['__laravel_notification_queued'] ?? $this->event->data['__illuminar_queued'] ?? false);
    }

    private function formatAddresses(?array $addresses): ?array
    {
        if (is_null($addresses)) {
            return null;
        }

        return collect($addresses)
            ->mapWithKeys(function ($address, $key) {
                if ($address instanceof Address) {
                    return [$address->getAddress() => $address->getName()];
                }

                return [$key => $address];
            })
            ->all();
    }
}
