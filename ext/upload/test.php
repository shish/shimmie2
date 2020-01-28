<?php declare(strict_types=1);
class UploadTest extends ShimmiePHPUnitTestCase
{
    public function testUploadPage()
    {
        $this->log_in_as_user();

        $this->get_page("upload");
        $this->assert_title("Upload");
    }

    public function testUpload()
    {
        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
    }

    public function testRejectDupe()
    {
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        try {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        } catch (UploadException $e) {
            $this->assertStringContainsString("already has hash", $e->getMessage());
        }
    }

    public function testRejectUnknownFiletype()
    {
        try {
            $this->post_image("index.php", "test");
        } catch (UploadException $e) {
            $this->assertStringContainsString("Invalid or corrupted file", $e->getMessage());
        }
    }

    public function testRejectHuge()
    {
        // FIXME: huge.dat is rejected for other reasons; manual testing shows that this works
        file_put_contents("data/huge.jpg", file_get_contents("tests/pbx_screenshot.jpg") . str_repeat("U", 1024*1024*3));
        try {
            $this->post_image("data/huge.jpg", "test");
            $this->assertTrue(false, "Uploading huge.jpg didn't fail...");
        }
        catch (UploadException $e) {
            $this->assertEquals("File too large (3.0MB > 1.0MB)", $e->error);
        }
        unlink("data/huge.jpg");
    }
}
