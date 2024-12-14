<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        $this->assertEquals("artist", TagCategories::get_tag_category("artist:bob"));
        $this->assertEquals("bob", TagCategories::get_tag_body("artist:bob"));

        $this->assertEquals(null, TagCategories::get_tag_category("bob"));
        $this->assertEquals("bob", TagCategories::get_tag_body("bob"));

        $this->assertEquals(null, TagCategories::get_tag_category("notacategory:bob"));
        $this->assertEquals("notacategory:bob", TagCategories::get_tag_body("notacategory:bob"));
    }
}
