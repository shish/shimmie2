<?php declare(strict_types=1);
require_once "core/imageboard/tag.php";

class TagTest extends \PHPUnit\Framework\TestCase
{
    public function test_caret()
    {
        $this->assertEquals("foo", Tag::decaret("foo"));
        $this->assertEquals("foo?", Tag::decaret("foo^q"));
        $this->assertEquals("a^b/c\\d?e&f", Tag::decaret("a^^b^sc^bd^qe^af"));
    }

    public function test_decaret()
    {
        $this->assertEquals("foo", Tag::caret("foo"));
        $this->assertEquals("foo^q", Tag::caret("foo?"));
        $this->assertEquals("a^^b^sc^bd^qe^af", Tag::caret("a^b/c\\d?e&f"));
    }
}
