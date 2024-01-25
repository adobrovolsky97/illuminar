<?php

namespace Adobrovolsky97\Illuminar\Tests\Payloads;

use Adobrovolsky97\Illuminar\Payloads\QueryMacroPayload;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Illuminate\Database\Connection;

/**
 * Class QueryMacroPayloadTest
 */
class QueryMacroPayloadTest extends TestCase
{
    /**
     * Testing string param
     *
     * @return void
     */
    public function testStringParam(): void
    {
        $queryMacroPayload = new QueryMacroPayload($this->getMockedBuilder(['string']));

        $result = $queryMacroPayload->toArray();

        $this->assertEquals("SELECT * FROM table where param = 'string'", $result['sql']);
        $this->assertEquals('test_connection', $result['connection']);
        $this->assertTrue($result['is_macro']);
    }

    /**
     * Testing int param
     *
     * @return void
     */
    public function testIntParam(): void
    {
        $queryMacroPayload = new QueryMacroPayload($this->getMockedBuilder([555]));

        $result = $queryMacroPayload->toArray();

        $this->assertEquals("SELECT * FROM table where param = 555", $result['sql']);
        $this->assertEquals('test_connection', $result['connection']);
        $this->assertTrue($result['is_macro']);
    }

    /**
     * Testing carbon param
     *
     * @return void
     */
    public function testCarbonParam(): void
    {
        $queryMacroPayload = new QueryMacroPayload($this->getMockedBuilder([new Carbon('2020-01-01')]));

        $result = $queryMacroPayload->toArray();

        $this->assertEquals("SELECT * FROM table where param = '2020-01-01 00:00:00'", $result['sql']);
        $this->assertEquals('test_connection', $result['connection']);
        $this->assertTrue($result['is_macro']);
    }

    /**
     * Testing string param
     *
     * @return void
     */
    public function testArrayParam(): void
    {
        $builder = $this->getMockedBuilder([['param', 'another_param', 52]], 'SELECT * FROM table where param in (?)');
        $queryMacroPayload = new QueryMacroPayload($builder);

        $result = $queryMacroPayload->toArray();

        $this->assertEquals("SELECT * FROM table where param in ('param', 'another_param', 52)", $result['sql']);
        $this->assertEquals('test_connection', $result['connection']);
        $this->assertTrue($result['is_macro']);
    }

    /**
     * Get mocked builder
     *
     * @param array $bindings
     * @param string $sql
     * @return MockObject
     */
    private function getMockedBuilder(array $bindings, string $sql = 'SELECT * FROM table where param = ?'): MockObject
    {
        // Mock Builder
        $builderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock Connection
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->method('getName')->willReturn('test_connection');

        $builderMock->method('toSql')->willReturn($sql);
        $builderMock->method('getBindings')->willReturn($bindings);
        $builderMock->method('getConnection')->willReturn($connectionMock);

        return $builderMock;
    }
}
