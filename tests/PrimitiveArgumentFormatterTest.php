<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use Closure;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery;
use stdClass;

/**
 * Class PrimitiveArgumentFormatterTest
 */
class PrimitiveArgumentFormatterTest extends TestCase
{
    /**
     * @var PrimitiveArgumentFormatter
     */
    private PrimitiveArgumentFormatter $formatter;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->formatter = app(PrimitiveArgumentFormatter::class);
    }

    /**
     * @return void
     */
    public function testConvertArray(): void
    {
        $result = $this->formatter->convertToPrimitive(['foo', 'bar']);

        $this->assertEquals([
            ['type' => 'string', 'data' => 'foo'],
            ['type' => 'string', 'data' => 'bar']
        ], $result);
    }

    /**
     * @return void
     */
    public function testConvertClosure(): void
    {
        $closure = function () {
            return 'illuminar is the best';
        };
        $result = $this->formatter->convertToPrimitive($closure);
        $this->assertEquals('closure', $result['type']);
        $this->assertInstanceOf(SerializableClosure::class, unserialize($result['data']));
    }

    /**
     * @return void
     */
    public function testConvertSerializableObject()
    {
        $object = new stdClass();
        $result = $this->formatter->convertToPrimitive($object);
        $this->assertEquals('object', $result['type']);
        $this->assertEquals($object, unserialize($result['data']));
    }

    /**
     * @return void
     */
    public function testConvertNonSerializableObject(): void
    {
        $object = Mockery::mock('PDO');
        $result = $this->formatter->convertToPrimitive($object);
        $this->assertEquals('string', $result['type']);
        $this->assertEquals('Object of class ' . get_class($object) . ' is not serializable', $result['data']);
    }

    /**
     * @return void
     */
    public function testConvertFromPrimitiveWithArray(): void
    {
        $result = $this->formatter->convertFromPrimitive([
            ['type' => 'string', 'data' => 'foo'],
            ['type' => 'string', 'data' => 'bar']
        ]);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    /**
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function testConvertFromPrimitiveWithClosure(): void
    {
        $closure = function () {
            return 'illuminar is the best';
        };
        $serializableClosure = new SerializableClosure($closure);
        $result = $this->formatter->convertFromPrimitive([
            'type' => 'closure',
            'data' => serialize($serializableClosure)
        ]);
        $this->assertInstanceOf(Closure::class, $result);
    }

    /**
     * @return void
     */
    public function testConvertFromPrimitiveWithObject(): void
    {
        $object = new stdClass();
        $result = $this->formatter->convertFromPrimitive([
            'type' => 'object',
            'data' => serialize($object)
        ]);
        $this->assertEquals($object, $result);
    }
}
