<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Traits\SubstituteBindings;
use Carbon\Carbon;

/**
 * Class SubstituteBindingsTest
 */
class SubstituteBindingsTest extends TestCase
{
    private object $substitutor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->substitutor = new class {
            use SubstituteBindings {
                replaceBindings as public;
            }
        };
    }

    public function testPositionalBindings(): void
    {
        $this->assertSame(
            "select * from users where id = 1 and name = 'John' and active = true and deleted_at is null and created_at < '2024-01-01 00:00:00'",
            $this->substitutor->replaceBindings(
                'select * from users where id = ? and name = ? and active = ? and deleted_at is ? and created_at < ?',
                [1, 'John', true, null, Carbon::parse('2024-01-01')]
            )
        );
    }

    public function testNullIsNotQuoted(): void
    {
        $this->assertSame('update users set name = null', $this->substitutor->replaceBindings('update users set name = ?', [null]));
    }

    public function testQuotesAreEscaped(): void
    {
        $this->assertSame("select * from users where name = 'O''Brien'", $this->substitutor->replaceBindings('select * from users where name = ?', ["O'Brien"]));
    }

    public function testQuestionMarksAndColonsInsideStringsAreIgnored(): void
    {
        $this->assertSame(
            "select '?', 'a:b', \"col?\" from t where time > '10:00' and id = 5",
            $this->substitutor->replaceBindings("select '?', 'a:b', \"col?\" from t where time > '10:00' and id = ?", [5])
        );
    }

    public function testPostgresCastWithPositionalBindings(): void
    {
        $this->assertSame("select id::text from t where id = 5", $this->substitutor->replaceBindings('select id::text from t where id = ?', [5]));
    }

    public function testMoreBindingsThanPlaceholdersDoesNotThrow(): void
    {
        $this->assertSame('select 1 from t where a = 1', $this->substitutor->replaceBindings('select 1 from t where a = ?', [1, 2]));
        $this->assertSame('select 1 from t where a = 1 and b = ?', $this->substitutor->replaceBindings('select 1 from t where a = ? and b = ?', [1]));
    }

    public function testPercentSignsAreKept(): void
    {
        $this->assertSame("select * from t where name like '%foo%' and x = '100%'", $this->substitutor->replaceBindings("select * from t where name like ? and x = '100%'", ['%foo%']));
    }

    public function testNamedBindingsWithCommonPrefix(): void
    {
        $this->assertSame(
            "update t set name = 'a', name_full = 'b'",
            $this->substitutor->replaceBindings('update t set name = :name, name_full = :name_full', ['name' => 'a', 'name_full' => 'b'])
        );
    }

    public function testBinaryValues(): void
    {
        $this->assertSame('select * from t where uuid = 0xb131', $this->substitutor->replaceBindings('select * from t where uuid = ?', ["\xB1\x31"]));
    }

    public function testBackedEnum(): void
    {
        $this->assertSame("select * from t where status = 'active'", $this->substitutor->replaceBindings('select * from t where status = ?', [Stubs\TestStatus::Active]));
    }
}
