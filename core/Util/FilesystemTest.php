<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class FilesystemTest extends TestCase
{
    public function test_warehouse_path(): void
    {
        $hash = "7ac19c10d6859415a9bf32f335f564ad";

        self::assertEquals(
            Filesystem::join_path("data/base", $hash),
            Filesystem::warehouse_path("base", $hash, false, 0)
        );

        self::assertEquals(
            Filesystem::join_path("data/base", "7a", $hash),
            Filesystem::warehouse_path("base", $hash, false, 1)
        );

        self::assertEquals(
            Filesystem::join_path("data/base", "7a", "c1", $hash),
            Filesystem::warehouse_path("base", $hash, false, 2)
        );

        self::assertEquals(
            Filesystem::join_path("data/base", "7a", "c1", "9c", "10", $hash),
            Filesystem::warehouse_path("base", $hash, false, 4)
        );

        self::assertEquals(
            Filesystem::join_path("data/base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 8)
        );

        self::assertEquals(
            new Path("data/base/7a/c1/9c/10/d6/85/94/15/a9/bf/32/f3/35/f5/64/ad/7ac19c10d6859415a9bf32f335f564ad"),
            Filesystem::warehouse_path("base", $hash, false, 50)
        );
    }

    public function test_path_to_tags(): void
    {
        self::assertEquals(
            [],
            Filesystem::path_to_tags(new Path("nope.jpg"))
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags(new Path("\\"))
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags(new Path("/"))
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags(new Path("C:\\"))
        );
        self::assertEquals(
            ["tag", "test"],
            Filesystem::path_to_tags(new Path("123 - test tag.jpg"))
        );
        self::assertEquals(
            ["foo", "bar"],
            Filesystem::path_to_tags(new Path("/foo/bar/baz.jpg"))
        );
        self::assertEquals(
            ["cake", "pie", "foo", "bar"],
            Filesystem::path_to_tags(new Path("/foo/bar/123 - cake pie.jpg"))
        );
        self::assertEquals(
            ["bacon", "lemon"],
            Filesystem::path_to_tags(new Path("\\bacon\\lemon\\baz.jpg"))
        );
        self::assertEquals(
            ["category:tag"],
            Filesystem::path_to_tags(new Path("/category:/tag/baz.jpg"))
        );
    }

    public function test_deltree(): void
    {
        $tmp = sys_get_temp_dir();
        $dir = "$tmp/test_deltree";
        mkdir($dir);
        file_put_contents("$dir/foo", "bar");
        mkdir("$dir/baz");
        file_put_contents("$dir/baz/.qux", "quux");
        self::assertTrue(file_exists($dir));
        self::assertTrue(file_exists("$dir/foo"));
        self::assertTrue(file_exists("$dir/baz"));
        self::assertTrue(file_exists("$dir/baz/.qux"));
        Filesystem::deltree(new Path($dir));
        self::assertFalse(file_exists($dir));
    }

    public function test_join_path(): void
    {
        self::assertEquals(
            new Path("one"),
            Filesystem::join_path("one")
        );

        self::assertEquals(
            new Path("one/two"),
            Filesystem::join_path("one", "two")
        );

        self::assertEquals(
            new Path("one/two/three"),
            Filesystem::join_path("one", "two", "three")
        );

        self::assertEquals(
            new Path("one/two/three"),
            Filesystem::join_path("one/two", "three")
        );

        self::assertEquals(
            new Path("/one/two/three/"),
            Filesystem::join_path("\\/////\\\\one/\///"."\\//two\/\\//\\//", "//\/\\\/three/\\/\/")
        );
    }
}
