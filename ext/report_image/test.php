<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReportImageTest extends ShimmiePHPUnitTestCase
{
    public function testReportImage(): void
    {
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::get_page("post/view/$image_id");

        // Add image report
        send_event(new AddReportedImageEvent(new ImageReport($image_id, Ctx::$user->id, "report details")));

        // Check that the report exists
        self::get_page("image_report/list");
        self::assert_title("Reported Posts");
        self::assert_text("report details");
        self::assert_text("$image_id");

        // Remove report
        $report_id = (int)Ctx::$database->get_one("SELECT id FROM image_reports");
        send_event(new RemoveReportedImageEvent($report_id));

        // Check that the report is gone
        self::get_page("image_report/list");
        self::assert_title("Reported Posts");
        self::assert_no_text("report details");

        # FIXME: test delete image from report screen
        # FIXME: test that >>123 works
    }
}
