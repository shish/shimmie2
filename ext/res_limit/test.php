<?php declare(strict_types=1);
class ResolutionLimitTest extends ShimmiePHPUnitTestCase
{
    public function testResLimitOK()
    {
        global $config;
        $config->set_int("upload_min_height", 0);
        $config->set_int("upload_min_width", 0);
        $config->set_int("upload_max_height", 2000);
        $config->set_int("upload_max_width", 2000);
        $config->set_string("upload_ratios", "4:3 16:9");

        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        //$this->assert_response(302);
        $this->assert_no_text("Post too large");
        $this->assert_no_text("Post too small");
        $this->assert_no_text("ratio");
    }

    public function testResLimitSmall()
    {
        global $config;
        $config->set_int("upload_min_height", 900);
        $config->set_int("upload_min_width", 900);
        $config->set_int("upload_max_height", -1);
        $config->set_int("upload_max_width", -1);
        $config->set_string("upload_ratios", "4:3 16:9");

        $this->log_in_as_user();
        try {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
            $this->assertTrue(false, "Invalid-size image was allowed");
        } catch (UploadException $e) {
            $this->assertEquals("Post too small", $e->getMessage());
        }
    }

    public function testResLimitLarge()
    {
        global $config;
        $config->set_int("upload_min_height", 0);
        $config->set_int("upload_min_width", 0);
        $config->set_int("upload_max_height", 100);
        $config->set_int("upload_max_width", 100);
        $config->set_string("upload_ratios", "4:3 16:9");

        try {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
            $this->assertTrue(false, "Invalid-size image was allowed");
        } catch (UploadException $e) {
            $this->assertEquals("Post too large", $e->getMessage());
        }
    }

    public function testResLimitRatio()
    {
        global $config;
        $config->set_int("upload_min_height", -1);
        $config->set_int("upload_min_width", -1);
        $config->set_int("upload_max_height", -1);
        $config->set_int("upload_max_width", -1);
        $config->set_string("upload_ratios", "16:9");

        try {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
            $this->assertTrue(false, "Invalid-size image was allowed");
        } catch (UploadException $e) {
            $this->assertEquals("Post needs to be in one of these ratios: 16:9", $e->getMessage());
        }
    }

    # reset to defaults, otherwise this can interfere with
    # other extensions' test suites
    public function tearDown(): void
    {
        global $config;
        $config->set_int("upload_min_height", -1);
        $config->set_int("upload_min_width", -1);
        $config->set_int("upload_max_height", -1);
        $config->set_int("upload_max_width", -1);
        $config->set_string("upload_ratios", "");

        parent::tearDown();
    }
}
