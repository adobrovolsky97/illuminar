<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestUserModel;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class QueryWatcherTest
 */
class QueryWatcherTest extends TestCase
{
    /**
     * @param $app
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->createTable();
    }

    /**
     * @return void
     */
    public function testRegistersQueriesAndDetectsQueryDuplicates(): void
    {
        illuminar()->trackQueries();

        for ($i = 0; $i < 10; $i++) {
            TestUserModel::create([
                'name'  => 'Andrew',
                'email' => 'andrew@illuminar.com',
            ]);
        }

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertCount(10, $data);
        // All 10 queries has the same hash, so they are duplicates
        $this->assertCount(1, array_unique(array_column($data, 'hash')));
    }

    /**
     * Testing that bindings are prepared correctly
     *
     * @return void
     */
    public function testBindings(): void
    {
        illuminar()->trackQueries();

        TestUserModel::query()
            ->where('id', 25)
            ->where('name', 'LIKE', "%User%")
            ->where('is_active', true)
            ->where('email', 'test@illuminar.com')
            ->where('created_at', '<=', Carbon::parse('2024-01-26'))
            ->first();

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(1, $data);

        $this->assertSame(
            <<<'SQL'
                select * from "test_users" where "id" = 25 and "name" LIKE '%User%' and "is_active" = true and "email" = 'test@illuminar.com' and "created_at" <= '2024-01-26 00:00:00' and "test_users"."deleted_at" is null limit 1
                SQL,
            $data[0]['sql']
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNamedBindings(): void
    {
        illuminar()->trackQueries();

        $this->app->get('db')->statement(<<<'SQL'
            update "test_users" set "email" = :email, "name" = :name where "is_active" = :is_active and "created_at" < :created_at
            SQL
            , [
                'email'      => 'test@illuminar.com',
                'name'       => 'Andrew',
                'is_active'  => false,
                'created_at' => Carbon::parse('2019-01-01'),
            ]);

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertCount(1, $data);

        $this->assertSame(
            <<<'SQL'
            update "test_users" set "email" = 'test@illuminar.com', "name" = 'Andrew' where "is_active" = false and "created_at" < '2019-01-01 00:00:00'
            SQL,
            $data[0]['sql']
        );
    }

    /**
     * @return void
     */
    public function testMacroCallsWithEloquentBuilder(): void
    {
        TestUserModel::query()
            ->withTrashed()
            ->where('is_active', false)
            ->illuminar()
            ->where('email', 'test@illuminar.com')
            ->illuminar()
            ->first();

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(2, $data);

        foreach ($data as $entry) {
            $this->assertEquals(QueryWatcher::getName(), $entry['type']);
            $this->assertTrue($entry['is_macro']);
        }

        $this->assertEquals(
            <<<'SQL'
            select * from "test_users" where "is_active" = false
            SQL,
            $data[0]['sql']
        );

        $this->assertEquals(
            <<<'SQL'
            select * from "test_users" where "is_active" = false and "email" = 'test@illuminar.com'
            SQL,
            $data[1]['sql']
        );
    }

    /**
     * @return void
     */
    public function testMacroCallsWithQueryBuilder(): void
    {
        DB::table('test_users')
            ->where('is_active', false)
            ->illuminar()
            ->where('email', 'test@illuminar.com')
            ->illuminar()
            ->first();

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(2, $data);

        foreach ($data as $entry) {
            $this->assertEquals(QueryWatcher::getName(), $entry['type']);
            $this->assertTrue($entry['is_macro']);
        }

        $this->assertEquals(
            <<<'SQL'
            select * from "test_users" where "is_active" = false
            SQL,
            $data[0]['sql']
        );

        $this->assertEquals(
            <<<'SQL'
            select * from "test_users" where "is_active" = false and "email" = 'test@illuminar.com'
            SQL,
            $data[1]['sql']
        );
    }

    /**
     * @return void
     */
    protected function createTable(): void
    {
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
