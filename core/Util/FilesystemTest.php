<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    public function test_warehouse_path(): void
    {
        $hash = "7ac19c10d6859415";

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", $hash),
            Filesystem::warehouse_path("base", $hash, false, 0)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", $hash),
            Filesystem::warehouse_path("base", $hash, false, 1)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", $hash),
            Filesystem::warehouse_path("base", $hash, false, 2)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", $hash),
            Filesystem::warehouse_path("base", $hash, false, 3)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", $hash),
            Filesystem::warehouse_path("base", $hash, false, 4)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", $hash),
            Filesystem::warehouse_path("base", $hash, false, 5)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", $hash),
            Filesystem::warehouse_path("base", $hash, false, 6)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", $hash),
            Filesystem::warehouse_path("base", $hash, false, 7)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 8)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 9)
        );

        self::assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 10)
        );
    }

    public function test_path_to_tags(): void
    {
        self::assertEquals(
            [],
            Filesystem::path_to_tags("nope.jpg")
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags("\\")
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags("/")
        );
        self::assertEquals(
            [],
            Filesystem::path_to_tags("C:\\")
        );
        self::assertEquals(
            ["test", "tag"],
            Filesystem::path_to_tags("123 - test tag.jpg")
        );
        self::assertEquals(
            ["foo", "bar"],
            Filesystem::path_to_tags("/foo/bar/baz.jpg")
        );
        self::assertEquals(
            ["cake", "pie", "foo", "bar"],
            Filesystem::path_to_tags("/foo/bar/123 - cake pie.jpg")
        );
        self::assertEquals(
            ["bacon", "lemon"],
            Filesystem::path_to_tags("\\bacon\\lemon\\baz.jpg")
        );
        self::assertEquals(
            ["category:tag"],
            Filesystem::path_to_tags("/category:/tag/baz.jpg")
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
        Filesystem::deltree($dir);
        self::assertFalse(file_exists($dir));
    }

    public function test_sanitize_path(): void
    {
        self::assertEquals(
            "one",
            Filesystem::sanitize_path("one")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one/two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\\\two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one//two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\\\\\two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one///two")
        );

        self::assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR,
            Filesystem::sanitize_path("\\/one/\\/\\/two\\/")
        );
    }

    public function test_join_path(): void
    {
        self::assertEquals(
            "one",
            Filesystem::join_path("one")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::join_path("one", "two")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            Filesystem::join_path("one", "two", "three")
        );

        self::assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            Filesystem::join_path("one/two", "three")
        );

        self::assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three".DIRECTORY_SEPARATOR,
            Filesystem::join_path("\\/////\\\\one/\///"."\\//two\/\\//\\//", "//\/\\\/three/\\/\/")
        );
    }
}
