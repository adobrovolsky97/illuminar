<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\MailPayload;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Illuminate\Mail\Events\MessageSent;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Class MailPreviewPayloadTest
 */
class MailPreviewPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $email = (new Email())
            ->subject('Subject')
            ->html('<p>Illuminar is the best one!</p>')
            ->from('from@illuminar.com')
            ->to('to@illuminar.com');

        $message = new SentMessage(
            $email,
            new Envelope(
                new Address('from@from.com', 'Test Name'),
                [new Address('recipient@test.com', 'Recipient Name')]
            )
        );

        $event = new MessageSent(new \Illuminate\Mail\SentMessage($message), ['__laravel_notification' => 'Notification']);

        $payload = new MailPayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('mailable_class', $result);
        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('queued', $result);
        $this->assertArrayHasKey('from', $result);
        $this->assertArrayHasKey('to', $result);
        $this->assertArrayHasKey('cc', $result);
        $this->assertArrayHasKey('bcc', $result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertArrayHasKey('caller', $result);

        $this->assertEquals(MailWatcher::getName(), $result['type']);
        $this->assertEquals('Notification', $result['mailable_class']);
        $this->assertEquals('Subject', $result['subject']);
        $this->assertFalse($result['queued']);
        $this->assertEquals(['from@illuminar.com' => ''], $result['from']);
        $this->assertEquals(['to@illuminar.com' => ''], $result['to']);
        $this->assertEquals('<p>Illuminar is the best one!</p>', $result['html']);
        $this->assertNotNull($result['uuid']);
        $this->assertNotNull($result['caller']);
        $this->assertNotNull($result['time']);
    }
}
