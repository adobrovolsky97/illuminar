<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Formatters\PrimitiveArgumentFormatter;
use stdClass;

/**
 * Class PrimitiveArgumentFormatterTest
 */
class PrimitiveArgumentFormatterTest extends TestCase
{
    private PrimitiveArgumentFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = app(PrimitiveArgumentFormatter::class);
    }

    public function testScalarsAreStoredAsIs(): void
    {
        $this->assertSame(['type' => 'string', 'data' => 'foo'], $this->formatter->convertToPrimitive('foo'));
        $this->assertSame(['type' => 'double', 'data' => 1.5], $this->formatter->convertToPrimitive(1.5));
        $this->assertSame(['type' => 'NULL', 'data' => null], $this->formatter->convertToPrimitive(null));
    }

    public function testArraysObjectsAndClosuresAreRenderedToHtml(): void
    {
        $object = new stdClass();
        $object->foo = 'bar';

        foreach ([['foo', 'bar'], $object, function () {
        }, new \PDO('sqlite::memory:')] as $value) {
            $result = $this->formatter->convertToPrimitive($value);

            $this->assertSame('html', $result['type']);
            $this->assertStringContainsString('sf-dump', $result['data']);
        }
    }

    public function testConvertFromPrimitive(): void
    {
        $this->assertSame('<b>x</b>', $this->formatter->convertFromPrimitive(['type' => 'html', 'data' => '<b>x</b>']));
        $this->assertStringContainsString('foo', $this->formatter->convertFromPrimitive(['type' => 'string', 'data' => 'foo']));
        $this->assertStringContainsString('nested', $this->formatter->convertFromPrimitive(['key' => ['nested' => 1]]));
    }

    public function testStoredSerializedDataIsNeverUnserialized(): void
    {
        $result = $this->formatter->convertFromPrimitive(['type' => 'object', 'data' => serialize(new stdClass())]);

        $this->assertStringContainsString('older Illuminar version', $result);
    }

    public function testUserArrayWithTypeKeyIsNotTreatedAsHtml(): void
    {
        // user data that looks like a stored primitive must be escaped
        $result = $this->formatter->convertFromPrimitive($this->formatter->convertToPrimitive(['type' => 'html', 'data' => '<img src=x onerror=alert(1)>']));

        $this->assertStringNotContainsString('<img', $result);
    }
}
