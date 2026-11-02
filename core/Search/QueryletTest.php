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

    public function testParameterNamingCollisionWithSimilarNames(): void
    {
        // This test reproduces the bug where parameter names like val1 and val12
        // collide during renaming. The str_replace(':val1', ':val11', ...)
        // incorrectly replaces ':val1' in ':val12', producing ':val112' instead of ':val121'
        Querylet::resetCounter();
        $q = new Querylet(
            "WHERE (field1 = :val1) AND (field2 = :val12)",
            ["val1" => "search1", "val12" => "search2"]
        );
        // Without the fix, this would fail because ':val1' in ':val12' gets replaced
        // first, turning it into ':val112', so the parameter for val12 becomes wrong
        self::assertSame("WHERE (field1 = :val11) AND (field2 = :val121)", $q->sql);
        self::assertSame(["val11" => "search1", "val121" => "search2"], $q->variables);
    }

    public function testParameterNamingCollisionWithManyTerms(): void
    {
        // This test simulates a search with many terms that could trigger the 500 error
        // mentioned in the bug report
        Querylet::resetCounter();
        $q = new Querylet(
            "WHERE val1 = :val1 AND val2 = :val2 AND val10 = :val10 AND val11 = :val11 AND val12 = :val12",
            [
                "val1" => "a",
                "val2" => "b",
                "val10" => "c",
                "val11" => "d",
                "val12" => "e",
            ]
        );
        self::assertSame(
            "WHERE val1 = :val11 AND val2 = :val21 AND val10 = :val101 AND val11 = :val111 AND val12 = :val121",
            $q->sql
        );
        self::assertSame(
            [
                "val11" => "a",
                "val21" => "b",
                "val101" => "c",
                "val111" => "d",
                "val121" => "e",
            ],
            $q->variables
        );
    }
}
