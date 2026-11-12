<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
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

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(HttpRequestWatcher::getName(), $data[0]['type']);
    }

    public function testSensitiveHeadersAreHiddenAndBodyIsTruncated(): void
    {
        config(['illuminar.http.max_body_size' => 1]);
        illuminar()->trackHttpRequests();

        Http::fake(['*' => Http::response(str_repeat('a', 5000), 200, ['Set-Cookie' => 'session=secret'])]);

        Http::withToken('secret-token')->get('https://test.com');

        $entry = StorageDriverFactory::getDriverForConfig()->getData()[0];

        $this->assertSame('********', $entry['request']['headers']['Authorization']);
        $this->assertSame('********', $entry['response']['headers']['Set-Cookie']);
        $this->assertStringContainsString('[truncated, 5000 bytes total]', $entry['response']['body']);
    }

    public function testFailedConnectionIsTracked(): void
    {
        if (!method_exists(\Illuminate\Http\Client\Factory::class, 'failedConnection')) {
            $this->markTestSkipped('Http::failedConnection() is not available in this Laravel version.');
        }

        illuminar()->trackHttpRequests();

        Http::fake(['*' => Http::failedConnection('Could not resolve host')]);

        try {
            Http::get('https://unknown.test');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
        }

        $entry = StorageDriverFactory::getDriverForConfig()->getData()[0] ?? null;

        $this->assertNotNull($entry);
        $this->assertTrue($entry['failed']);
        $this->assertNull($entry['status_code']);
    }
}
