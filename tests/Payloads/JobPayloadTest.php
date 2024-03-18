<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\JobPayload;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Adobrovolsky97\Illuminar\Tests\TestCase;

/**
 * Class JobPayloadTest
 */
class JobPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $job = new class implements ShouldQueue {
            use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

            public function handle(): void
            {
            }
        };

        $payload = new JobPayload('connection', 'queueName', ['displayName' => get_class($job)]);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('job_class', $result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('queue', $result);
        $this->assertArrayHasKey('job', $result);
        $this->assertArrayHasKey('exception', $result);
        $this->assertArrayHasKey('time', $result);

        $this->assertEquals(JobWatcher::getName(), $result['type']);
        $this->assertEquals(get_class($job), $result['job_class']);
        $this->assertNotNull($result['uuid']);
        $this->assertEquals('pending', $result['status']);
        $this->assertEquals('queueName', $result['queue']);
        $this->assertNotNull($result['job']);
        $this->assertNull($result['exception']);
        $this->assertNotNull($result['time']);
    }
}
