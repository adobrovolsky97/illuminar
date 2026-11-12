<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\EventPayload;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Tests\TestCase;

/**
 * Class EventPayloadTest
 */
class EventPayloadTest extends TestCase
{
    /**
     * Test event payload to array
     *
     * @return void
     */
    public function testToArray(): void
    {
        $payload = new EventPayload('SomeEvent', ['argument1', 'argument2']);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('event_name', $result);
        $this->assertArrayHasKey('event', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals(EventWatcher::getName(), $result['type']);
        $this->assertEquals('SomeEvent', $result['event_name']);
        $this->assertSame('html', $result['event']['type']);
        $this->assertStringContainsString('argument1', $result['event']['data']);
        $this->assertStringContainsString('argument2', $result['event']['data']);
    }
}
