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
    public function testToArrayWithPrimitiveArguments(): void
    {
        $result = (new DumpPayload('test', 123, true, null, false))->toArray();

        $this->assertCount(5, $result['data']);
        $this->assertSame(['type' => 'string', 'data' => 'test'], $result['data'][0]);
        $this->assertSame(['type' => 'integer', 'data' => 123], $result['data'][1]);
        $this->assertSame(['type' => 'boolean', 'data' => true], $result['data'][2]);
        $this->assertSame(['type' => 'NULL', 'data' => null], $result['data'][3]);
        // falsy values must not be lost
        $this->assertSame(['type' => 'boolean', 'data' => false], $result['data'][4]);
    }

    public function testObjectsAreRenderedToHtmlAtCaptureTime(): void
    {
        $object = new stdClass();
        $object->name = 'Illuminar';

        $result = (new DumpPayload($object, []))->toArray();

        $this->assertSame('html', $result['data'][0]['type']);
        $this->assertStringContainsString('Illuminar', $result['data'][0]['data']);
        // empty arrays must not be lost
        $this->assertSame('html', $result['data'][1]['type']);
    }

    public function testDumpClosure(): void
    {
        $result = (new DumpPayload(function () {
            return 'test';
        }))->toArray();

        $this->assertSame('html', $result['data'][0]['type']);
        $this->assertStringContainsString('Closure', $result['data'][0]['data']);
    }

    public function testFullTimestampIsStored(): void
    {
        $result = (new DumpPayload('test'))->toArray();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}[+-]\d{2}:\d{2}$/', $result['time']);
    }
}
