<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoCompleteTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");

        send_event(new UserLoginEvent(User::by_name(self::$anon_name)));
        $page = $this->get_page('api/internal/autocomplete', ["s" => "not-a-tag"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals("[]", $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "so"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"some_tag":1}', $page->data);
    }

    public function testCategories(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "artist:bob");

        $page = $this->get_page('api/internal/autocomplete', ["s" => "bob"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":1}', $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "art"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":1}', $page->data);

        $page = $this->get_page('api/internal/autocomplete', ["s" => "artist:"]);
        $this->assertEquals(200, $page->code);
        $this->assertEquals(PageMode::DATA, $page->mode);
        $this->assertEquals('{"artist:bob":1}', $page->data);
    }
}
