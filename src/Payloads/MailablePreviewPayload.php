<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Illuminate\Contracts\Support\Renderable;

/**
 * Preview of Mailable or notification MailMessage without sending it
 */
class MailablePreviewPayload extends Payload
{
    private Renderable $mailable;

    public function __construct(Renderable $mailable)
    {
        $this->mailable = $mailable;

        parent::__construct();
    }

    public function toArray(): array
    {
        $html = (string)$this->mailable->render();

        return array_merge($this->baseFields(MailWatcher::getName()), [
            'class'          => get_class($this->mailable),
            'mailable_class' => get_class($this->mailable),
            'subject'        => $this->mailable->subject ?? null,
            'is_preview'     => true,
            'html'           => $html,
            'mailable'       => $this->format($this->mailable),
        ]);
    }
}
