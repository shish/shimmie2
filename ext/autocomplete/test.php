<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoCompleteTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "link");
        send_event(new AddAliasEvent("prince_zelda", "link"));

        $this->log_out();
        $page = $this->get_page('api/internal/autocomplete', ["s" => "not-a-tag"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals("[]", $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "li"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"link":{"newtag":null,"count":1}}', $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "pr"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"prince_zelda":{"newtag":"link","count":1}}', $page->data);
    }

    public function testCategories(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "artist:bob");

        $page = $this->get_page('api/internal/autocomplete', ["s" => "bob"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "art"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "artist:"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":{"newtag":null,"count":1}}', $page->data);
    }

    public function testNonRoman(): void
    {
        $this->log_in_as_user();
        // test Cyrillic with various capitalisation
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "СОЮЗ советских Социалистических Республик");
        $this->log_out();

        $page = $this->get_page('api/internal/autocomplete', ["s" => "со"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals([
            "союз" => ["newtag" => null, "count" => 1],
            "советских" => ["newtag" => null, "count" => 1],
            "социалистических" => ["newtag" => null, "count" => 1]
        ], json_decode($page->data, true));
    }
}
