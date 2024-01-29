<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\ExceptionPayload;
use Exception;
use Illuminate\Log\Events\MessageLogged;
use Adobrovolsky97\Illuminar\Tests\TestCase;

/**
 * Class ExceptionPayloadTest
 */
class ExceptionPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $exception = new Exception('Test exception', 100);
        $event = new MessageLogged('error', 'Test exception', ['exception' => $exception]);
        $payload = new ExceptionPayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('class', $result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals('Exception', $result['class']);
        $this->assertEquals($exception->getFile(), $result['file']);
        $this->assertEquals($exception->getLine(), $result['line']);
        $this->assertEquals('Test exception', $result['message']);
        $this->assertEquals(100, $result['code']);
    }
}
