<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Tests\Stubs\TestUserModel;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Class RequestWatcherTest
 */
class RequestWatcherTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('illuminar.track.request', true);
        $app['config']->set('illuminar.allowed_environments', ['testing']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    protected function defineRoutes($router)
    {
        Route::get('/users', function () {
            foreach (range(1, 5) as $id) {
                TestUserModel::query()->find($id); // N+1
            }

            DB::select('select 1');
            Cache::get('missing');

            return 'ok';
        })->name('users.index');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tests run in one console context: start the "request" with a clean profile
        \Adobrovolsky97\Illuminar\Support\Context::reset();
    }

    public function testHttpRequestProfileWithNPlusOne(): void
    {
        $this->get('/users')->assertOk();

        $profiles = array_values(array_filter($this->entries(), function (array $entry) {
            return $entry['type'] === 'request';
        }));

        $this->assertCount(1, $profiles);
        $profile = $profiles[0];

        $this->assertSame('http', $profile['kind']);
        $this->assertSame('GET', $profile['method']);
        $this->assertSame(200, $profile['status_code']);
        $this->assertSame('users.index', $profile['route']);
        $this->assertSame(6, $profile['queries_count']);
        $this->assertSame(1, $profile['stats']['cache misses']);
        $this->assertGreaterThan(0, $profile['duration']);

        $this->assertCount(1, $profile['n_plus_one']);
        $this->assertSame(5, $profile['n_plus_one'][0]['count']);
        $this->assertStringContainsString('"test_users"."id" = 2', $profile['n_plus_one'][0]['sql']);
        $this->assertStringContainsString(__FILE__, $profile['n_plus_one'][0]['caller']);
    }

    public function testIlluminarOwnRequestsAreIgnored(): void
    {
        $this->getJson('/illuminar/data?types[]=dump')->assertOk();

        $this->assertEmpty($this->entries());
    }

    public function testCommandProfile(): void
    {
        $this->rerouteCommandEvents();
        $this->artisan('list')->assertSuccessful();

        $profile = collect($this->entries())->firstWhere('type', 'request');

        $this->assertSame('command', $profile['kind']);
        $this->assertSame('list', $profile['command']);
        $this->assertSame(0, $profile['exit_code']);
    }

    public function testIgnoredCommands(): void
    {
        $this->rerouteCommandEvents();
        $this->artisan('illuminar:clear')->assertSuccessful();

        $this->assertEmpty(collect($this->entries())->where('type', 'request'));
    }

    /**
     * Since Laravel 11 command events are raised by the console kernel (php artisan), not by $this->artisan()
     */
    private function rerouteCommandEvents(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        if (method_exists($kernel, 'rerouteSymfonyCommandEvents')) {
            $kernel->rerouteSymfonyCommandEvents();
        }
    }
}
