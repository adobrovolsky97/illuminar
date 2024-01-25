<?php

namespace Adobrovolsky97\Illuminar\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class TestJob
 */
class TestJob implements ShouldQueue
{
    /**
     * @var string
     */
    public string $connection = 'database';

    /**
     * @var string
     */
    public string $queue = 'test-queue';

    /**
     * @var array
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        //
    }
}
