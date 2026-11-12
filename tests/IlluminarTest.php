<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Facades\Illuminar as IlluminarFacade;
use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestMail;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use BadMethodCallException;
use Illuminate\Support\Facades\DB;

/**
 * Class IlluminarTest
 */
class IlluminarTest extends TestCase
{
    public function testDumpWithTagAndColor(): void
    {
        illuminar()->dump('test', ['a' => 1])->tag('my-tag')->red();

        $data = $this->entries();

        $this->assertCount(1, $data);
        $this->assertSame(DumpWatcher::getName(), $data[0]['type']);
        $this->assertSame('my-tag', $data[0]['tag']);
        $this->assertSame('red', $data[0]['color']);
        $this->assertCount(2, $data[0]['data']);
        $this->assertSame(__FILE__ . ':' . (__LINE__ - 9), $data[0]['caller']);
        $this->assertNotEmpty($data[0]['context_id']);
    }

    public function testHelperWithArgumentsDumps(): void
    {
        illuminar('first', 'second');
        IlluminarFacade::dump('third');

        $this->assertCount(2, $this->entries());
    }

    public function testUnknownColorThrows(): void
    {
        $this->expectException(BadMethodCallException::class);

        illuminar()->dump('test')->pink();
    }

    public function testEverythingIsNoopWhenDisabled(): void
    {
        config(['illuminar.enabled' => false]);

        illuminar()->dump('test')->tag('tag')->red();
        illuminar()->trackQueries();
        illuminar()->mailable(new TestMail([]));
        DB::table('sqlite_master')->illuminar()->get();
        collect([1, 2])->illuminar();

        $this->assertEmpty($this->entries());
    }

    public function testTrackMethodsAreChainableAndHaveAliases(): void
    {
        $illuminar = illuminar()->trackQueries()->trackModelEvents()->trackCache();

        $this->assertInstanceOf(Illuminar::class, $illuminar);
        $this->assertTrue(Illuminar::getWatcher('query')->enabled);
        $this->assertTrue(Illuminar::getWatcher('model')->enabled);
        $this->assertTrue(Illuminar::getWatcher('cache')->enabled);

        illuminar()->stopTrackingAll();

        $this->assertFalse(Illuminar::getWatcher('query')->enabled);
    }

    public function testUnknownMethodThrows(): void
    {
        $this->expectException(BadMethodCallException::class);

        illuminar()->trackSomething();
    }

    public function testWatchTracksOnlyInsideCallback(): void
    {
        $result = illuminar()->watch(function () {
            DB::select('select 1');

            return 'result';
        }, ['query']);

        DB::select('select 2');

        $this->assertSame('result', $result);
        $this->assertCount(1, $this->entries());
        $this->assertSame('select 1', $this->entries()[0]['sql']);
        $this->assertFalse(Illuminar::getWatcher(QueryWatcher::getName())->enabled);
    }

    public function testWatchersCanBeEnabledFromConfig(): void
    {
        config(['illuminar.track.query' => true]);
        Illuminar::initialize();

        DB::select('select 1');

        $this->assertCount(1, $this->entries());
    }

    public function testMailablePreview(): void
    {
        illuminar()->mailable(new TestMail(['key' => 'value']));

        $data = $this->entries();

        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_preview']);
        $this->assertStringContainsString('Test Mail', $data[0]['html']);
    }

    public function testShowEnvMasksSecrets(): void
    {
        $path = sys_get_temp_dir() . '/illuminar-test-' . uniqid() . '.env';
        file_put_contents($path, "APP_NAME=\"My App\"\n# comment\nDB_PASSWORD=secret\nBROKEN_LINE_WITHOUT_VALUE=\nSTRIPE_KEY=pk_live\n");

        $this->app->loadEnvironmentFrom(basename($path));
        $this->app->useEnvironmentPath(dirname($path));

        try {
            illuminar()->showEnv();
        } finally {
            unlink($path);
        }

        $html = $this->entries()[0]['data'][0]['data'];

        $this->assertStringContainsString('My App', $html);
        $this->assertStringNotContainsString('secret', $html);
        $this->assertStringNotContainsString('pk_live', $html);
        $this->assertSame('env', $this->entries()[0]['tag']);
    }

    public function testCollectionMacro(): void
    {
        $collection = collect([1, 2, 3])->illuminar()->map(function ($item) {
            return $item * 2;
        });

        $this->assertSame([2, 4, 6], $collection->all());
        $this->assertCount(1, $this->entries());
    }

    public function testInternalErrorsDoNotBreakApplication(): void
    {
        Illuminar::$throwInternalErrors = false;

        Illuminar::record(function () {
            throw new \RuntimeException('Broken payload');
        });

        $this->assertEmpty($this->entries());
    }
}
