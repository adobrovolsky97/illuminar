<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Class LogWatcherTest
 */
class LogWatcherTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('logging.default', 'null');
    }

    public function testLogsAreTracked(): void
    {
        illuminar()->trackLogs();

        Log::info('User registered', ['user_id' => 5]);
        logger()->warning('Low balance');

        illuminar()->stopTrackingLogs();
        Log::info('Not tracked');

        $data = $this->entries();

        $this->assertCount(2, $data);
        $this->assertSame('info', $data[0]['level']);
        $this->assertSame('User registered', $data[0]['message']);
        $this->assertStringContainsString('user_id', $data[0]['log_context']['data']);
        $this->assertStringContainsString(__FILE__, $data[0]['caller']);
        $this->assertSame('warning', $data[1]['level']);
    }

    public function testMinLevel(): void
    {
        config(['illuminar.logs.min_level' => 'warning']);
        illuminar()->trackLogs();

        Log::debug('debug');
        Log::error('error');

        $this->assertSame(['error'], array_column($this->entries(), 'level'));
    }

    public function testReportedExceptionsAreLeftToExceptionWatcher(): void
    {
        illuminar()->trackLogs()->trackExceptions();

        report(new RuntimeException('Boom'));

        $this->assertSame(['exception'], array_column($this->entries(), 'type'));
    }
}
