<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class HttpRequestWatcherTest
 */
class HttpRequestWatcherTest extends TestCase
{
    /**
     * @param $app
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        if (!class_exists(Client::class)) {
            $this->markTestSkipped('The "guzzlehttp/guzzle" composer package is required for this test.');
        }
    }

    /**
     * @return void
     */
    public function testHttpRequestEvent(): void
    {
        illuminar()->trackHttpRequests();

        Http::fake([
            '*' => Http::response(
                ['foo' => 'bar'],
                201,
                [
                    'Content-Type' => 'application/json',

                    'Cache-Control' => 'no-cache,private'
                ]
            ),
        ]);

        Http::withHeaders(['Accept-Language' => 'nl_BE'])->get('https://test.com/foo/bar');

        illuminar()->stopTrackingHttpRequests();

        Http::withHeaders(['Accept-Language' => 'nl_BE'])->get('https://test.com/foo/bar');

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(HttpRequestWatcher::getName(), $entry['type']);
    }
}
