<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    public function test_warehouse_path(): void
    {
        $hash = "7ac19c10d6859415";

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", $hash),
            Filesystem::warehouse_path("base", $hash, false, 0)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", $hash),
            Filesystem::warehouse_path("base", $hash, false, 1)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", $hash),
            Filesystem::warehouse_path("base", $hash, false, 2)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", $hash),
            Filesystem::warehouse_path("base", $hash, false, 3)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", $hash),
            Filesystem::warehouse_path("base", $hash, false, 4)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", $hash),
            Filesystem::warehouse_path("base", $hash, false, 5)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", $hash),
            Filesystem::warehouse_path("base", $hash, false, 6)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", $hash),
            Filesystem::warehouse_path("base", $hash, false, 7)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 8)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 9)
        );

        $this->assertEquals(
            Filesystem::join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            Filesystem::warehouse_path("base", $hash, false, 10)
        );
    }

    public function test_path_to_tags(): void
    {
        $this->assertEquals(
            [],
            Filesystem::path_to_tags("nope.jpg")
        );
        $this->assertEquals(
            [],
            Filesystem::path_to_tags("\\")
        );
        $this->assertEquals(
            [],
            Filesystem::path_to_tags("/")
        );
        $this->assertEquals(
            [],
            Filesystem::path_to_tags("C:\\")
        );
        $this->assertEquals(
            ["test", "tag"],
            Filesystem::path_to_tags("123 - test tag.jpg")
        );
        $this->assertEquals(
            ["foo", "bar"],
            Filesystem::path_to_tags("/foo/bar/baz.jpg")
        );
        $this->assertEquals(
            ["cake", "pie", "foo", "bar"],
            Filesystem::path_to_tags("/foo/bar/123 - cake pie.jpg")
        );
        $this->assertEquals(
            ["bacon", "lemon"],
            Filesystem::path_to_tags("\\bacon\\lemon\\baz.jpg")
        );
        $this->assertEquals(
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
        $this->assertTrue(file_exists($dir));
        $this->assertTrue(file_exists("$dir/foo"));
        $this->assertTrue(file_exists("$dir/baz"));
        $this->assertTrue(file_exists("$dir/baz/.qux"));
        Filesystem::deltree($dir);
        $this->assertFalse(file_exists($dir));
    }

    public function test_sanitize_path(): void
    {
        $this->assertEquals(
            "one",
            Filesystem::sanitize_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one/two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one//two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one\\\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::sanitize_path("one///two")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR,
            Filesystem::sanitize_path("\\/one/\\/\\/two\\/")
        );
    }

    public function test_join_path(): void
    {
        $this->assertEquals(
            "one",
            Filesystem::join_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            Filesystem::join_path("one", "two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            Filesystem::join_path("one", "two", "three")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            Filesystem::join_path("one/two", "three")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three".DIRECTORY_SEPARATOR,
            Filesystem::join_path("\\/////\\\\one/\///"."\\//two\/\\//\\//", "//\/\\\/three/\\/\/")
        );
    }
}
