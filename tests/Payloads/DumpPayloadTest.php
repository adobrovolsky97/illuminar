<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\DumpPayload;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use stdClass;

/**
 * Class DumpPayloadTest
 */
class DumpPayloadTest extends TestCase
{
    /**
     * Testing dump of primitive arguments
     *
     * @return void
     */
    public function testToArrayWithPrimitiveArguments(): void
    {
        $payload = new DumpPayload('test', 123, true);
        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('string', $result['data'][0]['type']);
        $this->assertEquals('integer', $result['data'][1]['type']);
        $this->assertEquals('boolean', $result['data'][2]['type']);
    }

    /**
     * Testing object argument
     *
     * @return void
     */
    public function testDumpObject(): void
    {
        $object = new stdClass();
        $payload = new DumpPayload($object);
        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('object', $result['data'][0]['type']);
    }

    /**
     * Testing array with closure argument
     *
     * @return void
     */
    public function testDumpClosure(): void
    {
        $closure = function () {
            return 'test';
        };
        $payload = new DumpPayload($closure);
        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('closure', $result['data'][0]['type']);
    }

    /**
     * Testing array with closure argument
     *
     * @return void
     */
    public function testDumpArrayOfClosures(): void
    {
        $closure = function () {
            return 'test';
        };
        $payload = new DumpPayload([$closure, $closure]);
        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertCount(2, $result['data'][0]);
        $this->assertEquals('closure', $result['data'][0][0]['type']);
        $this->assertEquals('closure', $result['data'][0][1]['type']);
    }
}
