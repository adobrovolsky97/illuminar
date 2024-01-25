<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Illuminate\Mail\Mailable;
use Mockery;

/**
 * Class IlluminarTest
 */
class IlluminarTest extends TestCase
{
    /**
     * @return void
     */
    public function testInitializeIlluminar(): void
    {
        $config = Mockery::mock('alias:Config');
        $config->shouldReceive('get')->with('illuminar.enabled')->andReturn(true);

        $app = Mockery::mock('alias:App');
        $app->shouldReceive('terminating');

        Illuminar::initialize();

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testMailablePreview(): void
    {
        $mailable = Mockery::mock(Mailable::class);
        $mailable->shouldReceive('render')->once();

        illuminar()->mailable($mailable);

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testDumpData(): void
    {
        $dumpWatcher = Mockery::mock(DumpWatcher::class);
        $dumpWatcher->shouldReceive('addDump')->andReturnSelf();

        $app = Mockery::mock('alias:App');
        $app->shouldReceive('make')->andReturn($dumpWatcher);

        illuminar()->dump('test');

        $this->assertTrue(true);
    }
}
