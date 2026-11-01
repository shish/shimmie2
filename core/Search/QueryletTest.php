<?php

declare(strict_types=1);

namespace Shimmie2;

final class QueryletTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleVariable(): void
    {
        Querylet::resetCounter();
        $q = new Querylet("foo = :foo", ["foo" => 12]);
        self::assertSame("foo = :foo1", $q->sql);
        self::assertSame(["foo1" => 12], $q->variables);
    }

    public function testAppendingPreservesNamespacing(): void
    {
        Querylet::resetCounter();
        $q1 = new Querylet("foo = :foo", ["foo" => 12]);
        $q2 = new Querylet(" OR foo = :foo", ["foo" => 34]);
        $q1->append($q2);
        self::assertSame($q1->sql, "foo = :foo1 OR foo = :foo2");
        self::assertSame($q1->variables, ["foo1" => 12, "foo2" => 34]);
    }

    public function testNoVariables(): void
    {
        Querylet::resetCounter();
        $q = new Querylet("SELECT * FROM table");
        self::assertSame("SELECT * FROM table", $q->sql);
        self::assertEmpty($q->variables);
    }

    public function testDuplicateParameterNamesInSameSql(): void
    {
        Querylet::resetCounter();
        $q = new Querylet(
            "foo = :foo OR bar = :foo",
            ["foo" => 12]
        );
        self::assertSame($q->sql, "foo = :foo1 OR bar = :foo1");
        self::assertSame($q->variables, ["foo1" => 12]);
    }
}
