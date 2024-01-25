<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Payloads\HttpRequestPayload;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Client\Response as HttpResponse;

/**
 * Class HttpRequestPayloadTest
 */
class HttpRequestPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $request = new HttpRequest(new Request('GET', 'https://test.com', ['header' => 'value'], 'body'));
        $response = new HttpResponse(new Response(200, ['header' => 'value'], 'body'));
        $event = new ResponseReceived($request, $response);
        $payload = new HttpRequestPayload($event);

        $result = $payload->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertArrayHasKey('caller', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('method', $result);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('response', $result);

        $this->assertEquals('https://test.com', $result['url']);
        $this->assertEquals('GET', $result['method']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('body', $result['request']['body']);
        $this->assertEquals([], $result['request']['data']);
        $this->assertEquals(200, $result['response']['status']);
        $this->assertEquals('body', $result['response']['body']);
        $this->assertNull($result['response']['duration']);
    }
}
