<?php

namespace Adobrovolsky97\Illuminar\Tests\Http;

use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Class IlluminarControllerTest
 */
class IlluminarControllerTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('illuminar.allowed_environments', ['local', 'testing']);
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    public function testUiIsAvailableLocally(): void
    {
        $this->get('/illuminar')
            ->assertOk()
            ->assertSee('window.Illuminar', false)
            ->assertSee('/illuminar/assets/app.js', false);
    }

    public function testAllRoutesReturn404WhenDisabled(): void
    {
        config(['illuminar.enabled' => false]);

        $this->get('/illuminar')->assertNotFound();
        $this->getJson('/illuminar/data?types[]=dump')->assertNotFound();
        $this->deleteJson('/illuminar/clear')->assertNotFound();
    }

    public function testForbiddenOutsideOfAllowedEnvironments(): void
    {
        config(['illuminar.allowed_environments' => ['local']]);

        $this->get('/illuminar')->assertForbidden();
        $this->getJson('/illuminar/data?types[]=dump')->assertForbidden();
        $this->deleteJson('/illuminar/clear')->assertForbidden();
    }

    public function testGateIsUsedWhenDefined(): void
    {
        Gate::define('viewIlluminar', function ($user = null) {
            return false;
        });

        $this->get('/illuminar')->assertForbidden();
    }

    public function testGetData(): void
    {
        illuminar()->dump('first')->tag('first-tag');
        illuminar()->trackQueries();
        DB::select('select 1');

        $response = $this->getJson('/illuminar/data?types[]=dump&types[]=query')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('type_counts.dump', 1)
            ->assertJsonPath('type_counts.query', 1);

        $dump = collect($response->json('data'))->firstWhere('type', 'dump');

        $this->assertSame(['first-tag'], $dump['tags']);
        $this->assertStringStartsWith('phpstorm://open?file=', $dump['caller_url']);
        $this->assertNotEmpty($dump['timestamp']);
        $this->assertNotEmpty($dump['content'][0]['html']);
    }

    public function testSearchAndContextFilters(): void
    {
        illuminar()->dump('needle')->tag('alpha');
        illuminar()->dump('haystack')->tag('beta');

        $this->getJson('/illuminar/data?types[]=dump&search=needle')->assertJsonCount(1, 'data');

        $contextId = $this->entries()[0]['context_id'];

        $this->getJson('/illuminar/data?types[]=dump&context_id=' . $contextId)->assertJsonCount(2, 'data');
        $this->getJson('/illuminar/data?types[]=dump&context_id=unknown')->assertJsonCount(0, 'data');
    }

    public function testClear(): void
    {
        illuminar()->dump('test');

        $this->deleteJson('/illuminar/clear')->assertNoContent();

        $this->assertEmpty($this->entries());
    }

    public function testAssetsAreServedFromPackage(): void
    {
        $this->get('/illuminar/assets/app.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

        $this->get('/illuminar/assets/composer.json')->assertNotFound();
    }

    public function testClearCommand(): void
    {
        illuminar()->dump('test');

        $this->artisan('illuminar:clear')->assertSuccessful();

        $this->assertEmpty($this->entries());
    }

    public function testTailCommandOnce(): void
    {
        illuminar()->dump('tail me');

        $this->artisan('illuminar:tail', ['--once' => true])
            ->expectsOutputToContain('tail me')
            ->assertSuccessful();
    }
}
