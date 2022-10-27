<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once "core/util.php";

class UtilTest extends TestCase
{
    public function test_warehouse_path()
    {
        $hash = "7ac19c10d6859415";

        $this->assertEquals(
            join_path(DATA_DIR, "base", $hash),
            warehouse_path("base", $hash, false, 0)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", $hash),
            warehouse_path("base", $hash, false, 1)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", $hash),
            warehouse_path("base", $hash, false, 2)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", $hash),
            warehouse_path("base", $hash, false, 3)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", $hash),
            warehouse_path("base", $hash, false, 4)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", $hash),
            warehouse_path("base", $hash, false, 5)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", $hash),
            warehouse_path("base", $hash, false, 6)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", $hash),
            warehouse_path("base", $hash, false, 7)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            warehouse_path("base", $hash, false, 8)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            warehouse_path("base", $hash, false, 9)
        );

        $this->assertEquals(
            join_path(DATA_DIR, "base", "7a", "c1", "9c", "10", "d6", "85", "94", "15", $hash),
            warehouse_path("base", $hash, false, 10)
        );
    }

    public function test_load_balance_url()
    {
        $hash = "7ac19c10d6859415";
        $ext = "jpg";

        // pseudo-randomly select one of the image servers, balanced in given ratio
        $this->assertEquals(
            "https://baz.mycdn.com/7ac19c10d6859415.jpg",
            load_balance_url("https://{foo=10,bar=5,baz=5}.mycdn.com/$hash.$ext", $hash)
        );

        // N'th and N+1'th results should be different
        $this->assertNotEquals(
            load_balance_url("https://{foo=10,bar=5,baz=5}.mycdn.com/$hash.$ext", $hash, 0),
            load_balance_url("https://{foo=10,bar=5,baz=5}.mycdn.com/$hash.$ext", $hash, 1)
        );
    }

    public function test_path_to_tags()
    {
        $this->assertEquals(
            "",
            path_to_tags("nope.jpg")
        );
        $this->assertEquals(
            "",
            path_to_tags("\\")
        );
        $this->assertEquals(
            "",
            path_to_tags("/")
        );
        $this->assertEquals(
            "",
            path_to_tags("C:\\")
        );
        $this->assertEquals(
            "test tag",
            path_to_tags("123 - test tag.jpg")
        );
        $this->assertEquals(
            "foo bar",
            path_to_tags("/foo/bar/baz.jpg")
        );
        $this->assertEquals(
            "cake pie foo bar",
            path_to_tags("/foo/bar/123 - cake pie.jpg")
        );
        $this->assertEquals(
            "bacon lemon",
            path_to_tags("\\bacon\\lemon\\baz.jpg")
        );
        $this->assertEquals(
            "category:tag",
            path_to_tags("/category:/tag/baz.jpg")
        );
    }
}
