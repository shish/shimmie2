<?php
require_once "core/util.php";

class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function test_sanitize_path()
    {

        $this->assertEquals(
            "one",
            sanitize_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one/two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one//two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one///two")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR,
            sanitize_path("\\/one/\\/\\/two\\/")
        );

    }

    public function test_join_path()
    {
        $this->assertEquals(
            "one",
            join_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            join_path("one","two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            join_path("one","two","three")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            join_path("one/two","three")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three".DIRECTORY_SEPARATOR,
            join_path("\\/////\\\\one/\///"."\\//two\/\\//\\//","//\/\\\/three/\\/\/")
        );
    }

    public function test_warehouse_path()
    {
        $hash = "7ac19c10d6859415";

        $this->assertEquals(
            join_path(DATA_DIR,"base",$hash),
            warehouse_path("base",$hash,false, 0)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a",$hash),
            warehouse_path("base",$hash,false, 1)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1",$hash),
            warehouse_path("base",$hash,false, 2)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c",$hash),
            warehouse_path("base",$hash,false, 3)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10",$hash),
            warehouse_path("base",$hash,false, 4)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6",$hash),
            warehouse_path("base",$hash,false, 5)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6","85",$hash),
            warehouse_path("base",$hash,false, 6)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6","85","94",$hash),
            warehouse_path("base",$hash,false, 7)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6","85","94","15",$hash),
            warehouse_path("base",$hash,false, 8)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6","85","94","15",$hash),
            warehouse_path("base",$hash,false, 9)
        );

        $this->assertEquals(
            join_path(DATA_DIR,"base","7a","c1","9c","10","d6","85","94","15",$hash),
            warehouse_path("base",$hash,false, 10)
        );

    }
}
