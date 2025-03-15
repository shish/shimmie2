<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        self::assertEquals("artist", TagCategories::get_tag_category("artist:bob"));
        self::assertEquals("bob", TagCategories::get_tag_body("artist:bob"));

        self::assertEquals(null, TagCategories::get_tag_category("bob"));
        self::assertEquals("bob", TagCategories::get_tag_body("bob"));

        self::assertEquals(null, TagCategories::get_tag_category("notacategory:bob"));
        self::assertEquals("notacategory:bob", TagCategories::get_tag_body("notacategory:bob"));
    }
}
