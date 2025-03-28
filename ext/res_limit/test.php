<?php

declare(strict_types=1);

namespace Shimmie2;

final class ResolutionLimitTest extends ShimmiePHPUnitTestCase
{
    public function testResLimitOK(): void
    {
        $config = Ctx::$config;
        $config->set("upload_min_height", 0);
        $config->set("upload_min_width", 0);
        $config->set("upload_max_height", 2000);
        $config->set("upload_max_width", 2000);
        $config->set("upload_ratios", "4:3 16:9");

        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        //self::assert_response(302);
        self::assertNotNull(Image::by_id($image_id));
    }

    public function testResLimitSmall(): void
    {
        $config = Ctx::$config;
        $config->set("upload_min_height", 900);
        $config->set("upload_min_width", 900);
        $config->set("upload_max_height", -1);
        $config->set("upload_max_width", -1);
        $config->set("upload_ratios", "4:3 16:9");

        self::log_in_as_user();
        $e = self::assertException(UploadException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        });
        self::assertEquals("Post too small", $e->getMessage());
    }

    public function testResLimitLarge(): void
    {
        $config = Ctx::$config;
        $config->set("upload_min_height", 0);
        $config->set("upload_min_width", 0);
        $config->set("upload_max_height", 100);
        $config->set("upload_max_width", 100);
        $config->set("upload_ratios", "4:3 16:9");

        $e = self::assertException(UploadException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        });
        self::assertEquals("Post too large", $e->getMessage());
    }

    public function testResLimitRatio(): void
    {
        $config = Ctx::$config;
        $config->set("upload_min_height", -1);
        $config->set("upload_min_width", -1);
        $config->set("upload_max_height", -1);
        $config->set("upload_max_width", -1);
        $config->set("upload_ratios", "16:9");

        $e = self::assertException(UploadException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        });
        self::assertEquals("Post needs to be in one of these ratios: 16:9", $e->getMessage());
    }
}
