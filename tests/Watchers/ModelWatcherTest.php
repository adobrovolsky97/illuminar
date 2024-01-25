<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestUserModel;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ModelWatcherTest
 */
class ModelWatcherTest extends TestCase
{
    /**
     * @param $app
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $this->createTable();
    }

    /**
     * @return void
     */
    public function testRecordModelEvents(): void
    {
        illuminar()->trackModels();

        $model = TestUserModel::create([
            'name'  => 'Andrew',
            'email' => 'andrew@illuminar.com',
        ]);

        $model->update(['name' => 'John']);
        $model->delete();
        $model->restore();

        illuminar()->stopTrackingModels();
        $model->update(['name' => 'Andrew']);

        $batch = collect(DataCollector::getBatch());

        // 5 b/c of soft deletes, it performs 2 queries: update and delete
        $this->assertCount(5, $batch);
        $this->assertCount(5, $batch->where('type', ModelWatcher::getName()));
    }

    /**
     * @return void
     */
    private function createTable(): void
    {
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
