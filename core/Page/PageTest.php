<?php

declare(strict_types=1);

namespace Shimmie2;

final class PageTest extends ShimmiePHPUnitTestCase
{
    public function test_page(): void
    {
        $page = new Page();
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_file(): void
    {
        $page = new Page();
        $page->set_file(MimeType::JPEG, new Path("tests/pbx_screenshot.jpg"));
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_data(): void
    {
        $page = new Page();
        $page->set_data(MimeType::TEXT, "hello world");
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_redirect(): void
    {
        $page = new Page();
        $page->set_redirect(Url::parse("/new/page"));
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_gen_disposition_header(): void
    {
        $page = new Page();

        // defaults
        self::assertEquals("inline", $page->gen_disposition_header());
        self::assertEquals("attachment", $page->gen_disposition_header("attachment"));

        // plain filename
        self::assertEquals(
            "inline; filename=\"test.jpg\"; filename*=UTF-8''test.jpg",
            $page->gen_disposition_header("inline", "test.jpg")
        );
        self::assertEquals(
            "attachment; filename=\"test.jpg\"; filename*=UTF-8''test.jpg",
            $page->gen_disposition_header("attachment", "test.jpg")
        );

        // spaces
        self::assertEquals(
            "inline; filename=\"my file.jpg\"; filename*=UTF-8''my%20file.jpg",
            $page->gen_disposition_header("inline", "my file.jpg")
        );

        // double quotes are escaped in the quoted form
        self::assertEquals(
            "inline; filename=\"my \\\"quoted\\\" file.jpg\"; filename*=UTF-8''my%20%22quoted%22%20file.jpg",
            $page->gen_disposition_header("inline", 'my "quoted" file.jpg')
        );

        // single quotes are left alone in the quoted form
        self::assertEquals(
            "inline; filename=\"it's.jpg\"; filename*=UTF-8''it%27s.jpg",
            $page->gen_disposition_header("inline", "it's.jpg")
        );

        // backslashes are escaped in the quoted form
        self::assertEquals(
            "inline; filename=\"back\\\\slash.jpg\"; filename*=UTF-8''back%5Cslash.jpg",
            $page->gen_disposition_header("inline", 'back\\slash.jpg')
        );

        // header-significant special characters
        self::assertEquals(
            "inline; filename=\"a;b,c=d%e.jpg\"; filename*=UTF-8''a%3Bb%2Cc%3Dd%25e.jpg",
            $page->gen_disposition_header("inline", "a;b,c=d%e.jpg")
        );

        // UTF-8
        self::assertEquals(
            "inline; filename=\"café.jpg\"; filename*=UTF-8''caf%C3%A9.jpg",
            $page->gen_disposition_header("inline", "café.jpg")
        );
        self::assertEquals(
            "inline; filename=\"日本語.jpg\"; filename*=UTF-8''%E6%97%A5%E6%9C%AC%E8%AA%9E.jpg",
            $page->gen_disposition_header("inline", "日本語.jpg")
        );
    }

    public function test_subNav(): void
    {
        // the default theme doesn't send this, so let's have
        // a random test manually

        self::log_in_as_admin(); // show the most links

        $e = send_event(new PageSubNavBuildingEvent("system"));
        self::assertGreaterThan(0, count($e->links));

        $e = send_event(new PageSubNavBuildingEvent("posts"));
        self::assertGreaterThan(0, count($e->links));
    }
}
