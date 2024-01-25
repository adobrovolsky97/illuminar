<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Illuminate\Mail\Mailable;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use ReflectionException;

/**
 * Class MailablePreviewPayload
 */
class MailablePreviewPayload extends Payload
{
    /**
     * @var Mailable
     */
    private Mailable $mailable;

    /**
     * @param Mailable $mailable
     */
    public function __construct(Mailable $mailable)
    {
        $this->mailable = $mailable;

        parent::__construct();
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws PhpVersionNotSupportedException
     */
    public function toArray(): array
    {
        return [
            'uuid'     => $this->getUuid(),
            'type'     => MailWatcher::getName(),
            'class'    => get_class($this->mailable),
            'html'     => $this->mailable->render(),
            'mailable' => app(PrimitiveArgumentFormatter::class)->convertToPrimitive($this->mailable),
            'caller'   => $this->getCaller(),
            'time'     => now()->format('H:i:s'),
        ];
    }
}
