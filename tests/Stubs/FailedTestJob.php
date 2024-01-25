<?php

namespace Adobrovolsky97\Illuminar\Tests\Stubs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class FailedTestJob
 */
class FailedTestJob implements ShouldQueue
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
     * @throws Exception
     */
    public function handle(): void
    {
        throw new Exception('Test exception');
    }
}
