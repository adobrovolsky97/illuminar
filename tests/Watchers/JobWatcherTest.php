<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\Stubs\FailedTestJob;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestJob;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class JobWatcherTest
 */
class JobWatcherTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('queue.failed.database', 'testbench');

        $app->get('config')->set('logging.default', 'syslog');

        $this->createJobsTable();
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testJobProcessing(): void
    {
        illuminar()->trackJobs();

        $this->app->get(Dispatcher::class)->dispatch(new TestJob(['key' => 'value']));

        illuminar()->stopTrackingJobs();

        $this->app->get(Dispatcher::class)->dispatch(new TestJob(['key' => 'value']));

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(JobWatcher::getName(), $entry['type']);
        $this->assertEquals('queued', $entry['status']);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once'     => true,
            '--queue'    => 'test-queue',
        ])->run();

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals('processed', $entry['status']);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testJobFailure(): void
    {
        illuminar()->trackJobs();

        $this->app->get(Dispatcher::class)->dispatch(new FailedTestJob(['key' => 'value']));

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once'     => true,
            '--queue'    => 'test-queue',
        ])->run();

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(JobWatcher::getName(), $entry['type']);
        $this->assertEquals('failed', $entry['status']);
        $this->assertNotNull($entry['exception']);
    }

    /**
     * @return void
     */
    private function createJobsTable(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->uuid();
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
}
