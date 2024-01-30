<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\CachePayload;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;

/**
 * Class CachePayloadTest
 */
class CachePayloadTest extends TestCase
{
    /**
     * Test cache hit event
     *
     * @return void
     */
    public function testCacheHitEvent(): void
    {
        $event = new CacheHit('cache_key', 'cache_value', ['tag1', 'tag2']);
        $payload = new CachePayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('hit', $result['event']);
        $this->assertEquals('cache_key', $result['key']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertEquals(['type' => 'string', 'data' => 'cache_value'], $result['value']);
    }

    /**
     * Test cache missed event
     *
     * @return void
     */
    public function testCacheMissedEvent(): void
    {
        $event = new CacheMissed('cache_key', ['tag1', 'tag2']);
        $payload = new CachePayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('missed', $result['event']);
        $this->assertEquals('cache_key', $result['key']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertNull($result['value']);
    }

    /**
     * Test key written event
     *
     * @return void
     */
    public function testKeyWrittenEvent(): void
    {
        $event = new KeyWritten('cache_key', 'cache_value', 60, ['tag1', 'tag2']);
        $payload = new CachePayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('written', $result['event']);
        $this->assertEquals('cache_key', $result['key']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertEquals(['type' => 'string', 'data' => 'cache_value'], $result['value']);
    }

    /**
     * Test key forgotten event
     *
     * @return void
     */
    public function testKeyForgottenEvent(): void
    {
        $event = new KeyForgotten('cache_key', ['tag1', 'tag2']);
        $payload = new CachePayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('forgotten', $result['event']);
        $this->assertEquals('cache_key', $result['key']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertNull($result['value']);
    }
}
