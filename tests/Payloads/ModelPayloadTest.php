<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\ModelPayload;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\MockObject\Exception;

/**
 * Class ModelPayloadTest
 */
class ModelPayloadTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testToArray(): void
    {
        $model = $this->createMock(Model::class);
        $model->method('getKey')->willReturn(1);
        $model->method('toArray')->willReturn(['id' => 1, 'name' => 'Test']);
        $model->method('getChanges')->willReturn(['name' => 'Test']);
        $model->method('getOriginal')->willReturn('Original');

        $payload = new ModelPayload('eloquent.created: App\\Models\\User', ['model' => $model]);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('model_class', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertArrayHasKey('new_attributes', $result);
        $this->assertArrayHasKey('original_attributes', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals(ModelWatcher::getName(), $result['type']);
        $this->assertEquals('created', $result['action']);
        $this->assertEquals(get_class($model), $result['model_class']);
        $this->assertEquals(1, $result['primary_key']);
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $result['new_attributes']);
        $this->assertEquals([], $result['original_attributes']);
        $this->assertNotNull($result['uuid']);
        $this->assertNotNull($result['caller']);
        $this->assertNotNull($result['time']);
    }
}
