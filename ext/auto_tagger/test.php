<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoTaggerTest extends ShimmiePHPUnitTestCase
{
    public function testAutoTaggerList(): void
    {
        $this->get_page('auto_tag/list');
        self::assert_response(200);
        self::assert_title("Auto-Tag");
    }

    public function testAutoTaggerListReadOnly(): void
    {
        $this->log_in_as_user();
        $this->get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("value=\"Add\"");

        $this->log_out();
        $this->get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("value=\"Add\"");
    }

    public function testAutoTagger(): void
    {
        $this->log_in_as_admin();

        $this->get_page("auto_tag/export/auto_tag.csv");
        self::assert_no_text("test1");

        send_event(new AddAutoTagEvent("test1", "test2"));
        $this->get_page('auto_tag/list');
        self::assert_text("test1");
        self::assert_text("test2");
        $this->get_page("auto_tag/export/auto_tag.csv");
        self::assert_text('"test1","test2"');

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        $this->get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test1 test2");
        $this->delete_image($image_id);

        send_event(new AddAutoTagEvent("test2", "test3"));

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        $this->get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test1 test2 test3");
        $this->delete_image($image_id);

        send_event(new DeleteAutoTagEvent("test1"));
        send_event(new DeleteAutoTagEvent("test2"));
        $this->get_page('auto_tag/list');
        self::assert_title("Auto-Tag");
        self::assert_no_text("test1");
        self::assert_no_text("test2");
        self::assert_no_text("test3");
    }
}
