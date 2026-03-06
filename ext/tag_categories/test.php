<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        self::assertSame("artist", TagCategories::get_tag_category("artist:bob"));
        self::assertSame("bob", TagCategories::get_tag_body("artist:bob"));

        self::assertSame(null, TagCategories::get_tag_category("bob"));
        self::assertSame("bob", TagCategories::get_tag_body("bob"));

        self::assertSame(null, TagCategories::get_tag_category("notacategory:bob"));
        self::assertSame("notacategory:bob", TagCategories::get_tag_body("notacategory:bob"));
    }
}
