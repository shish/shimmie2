<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoCompleteTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "link");
        send_event(new AddAliasEvent("prince_zelda", "link"));

        self::log_out();
        $page = self::get_page('api/internal/autocomplete', ["s" => "not-a-tag"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals("[]", $page->data);

        $page = self::get_page('api/internal/autocomplete', ["s" => "li"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals('{"link":{"newtag":null,"count":1}}', $page->data);

        $page = self::get_page('api/internal/autocomplete', ["s" => "pr"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals('{"prince_zelda":{"newtag":"link","count":1}}', $page->data);
    }

    public function testCategories(): void
    {
        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "artist:bob");

        $page = self::get_page('api/internal/autocomplete', ["s" => "bob"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);

        $page = self::get_page('api/internal/autocomplete', ["s" => "art"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);

        $page = self::get_page('api/internal/autocomplete', ["s" => "artist:"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);
    }

    public function testCyrillic(): void
    {
        self::log_in_as_user();

        // insert uppercase, lowercase, mixed case, and unrelated-words into the database
        $this->post_image("tests/pbx_screenshot.jpg", "СОЮЗ советских Социалистических Республик");

        // check that lowercase search returns all three cases of matching words
        $page = self::get_page('api/internal/autocomplete', ["s" => "со"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEqualsCanonicalizing(["СОЮЗ", "советских", "Социалистических"], array_keys(json_decode($page->data, true)));

        // check that uppercase search returns all three cases of matching words
        $page = self::get_page('api/internal/autocomplete', ["s" => "СО"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        self::assertEqualsCanonicalizing(["СОЮЗ", "советских", "Социалистических"], array_keys(json_decode($page->data, true)));
    }
}
