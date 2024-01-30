<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\MailablePreviewPayload;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use PHPUnit\Framework\MockObject\Exception;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Mail\Mailable;
use ReflectionException;

/**
 * Class MailablePreviewPayloadTest
 */
class MailablePreviewPayloadTest extends TestCase
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testToArray(): void
    {
        $mailable = $this->createMock(Mailable::class);
        $mailable->method('render')->willReturn('html content');

        $payload = new MailablePreviewPayload($mailable);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('class', $result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('mailable', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals(MailWatcher::getName(), $result['type']);
        $this->assertEquals(get_class($mailable), $result['class']);
        $this->assertEquals('html content', $result['html']);
        $this->assertNotNull($result['mailable']);
        $this->assertNotNull($result['uuid']);
        $this->assertNotNull($result['caller']);
        $this->assertNotNull($result['time']);
    }
}
