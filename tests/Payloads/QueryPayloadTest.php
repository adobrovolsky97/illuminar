<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\QueryPayload;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Connection;
use Adobrovolsky97\Illuminar\Tests\TestCase;

/**
 * Class QueryPayloadTest
 */
class QueryPayloadTest extends TestCase
{
    /**
     * Testing payload to array
     *
     * @return void
     */
    public function testToArray():void
    {
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->method('getName')->willReturn('test_connection');

        $event = new QueryExecuted('SELECT * FROM users WHERE id = ?', [1], 1, $connectionMock);
        $payload = new QueryPayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('connection', $result);
        $this->assertArrayHasKey('execution_time', $result);
        $this->assertArrayHasKey('is_slow', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals('SELECT * FROM users WHERE id = 1', $result['sql']);
        $this->assertEquals(md5('SELECT * FROM users WHERE id = ?'), $result['hash']);
        $this->assertEquals('test_connection', $result['connection']);
        $this->assertEquals(1, $result['execution_time']);
        $this->assertFalse($result['is_slow']);
    }
}
