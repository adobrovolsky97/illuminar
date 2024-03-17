<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
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

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(JobWatcher::getName(), $data[0]['type']);
        $this->assertEquals('pending', $data[0]['status']);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once'     => true,
            '--queue'    => 'test-queue',
        ])->run();

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(1, $data);

        $this->assertEquals('processed', $data[0]['status']);
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

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(1, $data);

        $this->assertEquals(JobWatcher::getName(), $data[0]['type']);
        $this->assertEquals('failed', $data[0]['status']);
        $this->assertNotNull($data[0]['exception']);
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
