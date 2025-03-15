<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoTaggerTest extends ShimmiePHPUnitTestCase
{
    public function testAutoTaggerList(): void
    {
        self::get_page('auto_tag/list');
        self::assert_response(200);
        self::assert_title("Auto-Tag");
    }

    public function testAutoTaggerListReadOnly(): void
    {
        self::log_in_as_user();
        self::get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("value=\"Add\"");

        self::log_out();
        self::get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("value=\"Add\"");
    }

    public function testAutoTagger(): void
    {
        self::log_in_as_admin();

        self::get_page("auto_tag/export/auto_tag.csv");
        self::assert_no_text("test1");

        send_event(new AddAutoTagEvent("test1", "test2"));
        self::get_page('auto_tag/list');
        self::assert_text("test1");
        self::assert_text("test2");
        self::get_page("auto_tag/export/auto_tag.csv");
        self::assert_text('"test1","test2"');

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        self::get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test1 test2");
        $this->delete_image($image_id);

        send_event(new AddAutoTagEvent("test2", "test3"));

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        self::get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test1 test2 test3");
        $this->delete_image($image_id);

        send_event(new DeleteAutoTagEvent("test1"));
        send_event(new DeleteAutoTagEvent("test2"));
        self::get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("test1");
        self::assert_no_text("test2");
        self::assert_no_text("test3");
    }
}
