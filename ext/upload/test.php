<?php declare(strict_types=1);
class UploadTest extends ShimmiePHPUnitTestCase
{
    public function testUploadPage()
    {
        $this->log_in_as_user();

        $this->get_page("upload");
        $this->assert_title("Upload");
    }

    // Because $this->post_image() sends the event directly
    public function testRawUpload()
    {
        global $database;

        $this->log_in_as_user();
        $_FILES = [
            'data0' => [
                'name' => ['puppy-hugs.jpg'],
                'type' => ['image/jpeg'],
                'tmp_name' => ['tests/bedroom_workshop.jpg'],
                'error' => [0],
                'size' => [271386],
            ],
            'data1' => [
                'name' => ['cat-hugs-2.jpg', 'cat-hugs-3.jpg', 'cat-hugs.jpg'],
                'type' => ['image/png', 'image/jpeg', 'image/svg'],
                'tmp_name' => ['tests/favicon.png', 'tests/pbx_screenshot.jpg', 'tests/test.svg'],
                'error' => [0, 0, 0],
                'size' => [110361, 64021, 421410],
            ],
            'data2' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [4],
                'size' => [0],
            ]
        ];
        $page = $this->post_page("upload", ["tags0"=>"foo bar"]);
        $this->assert_response(302);
        $this->assertStringStartsWith("/test/post/list/poster=test/1", $page->redirect);

        $this->assertEquals(4, $database->get_one("SELECT COUNT(*) FROM images"));
    }

    public function testRawReplace()
    {
        global $database;

        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        $_FILES = [
            'data' => [
                'name' => ['puppy-hugs.jpg'],
                'type' => ['image/jpeg'],
                'tmp_name' => ['tests/bedroom_workshop.jpg'],
                'error' => [0],
                'size' => [271386],
            ]
        ];

        $page = $this->post_page("upload/replace", ["image_id"=>$image_id]);
        $this->assert_response(302);
        $this->assertEquals("/test/post/view/$image_id", $page->redirect);

        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM images"));
    }

    public function testUpload()
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->assertGreaterThan(0, $image_id);

        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: computer pbx screenshot");
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
        $image_id = $this->post_image("index.php", "test");
        $this->assertEquals(-1, $image_id);  // no file handler claimed this
    }

    public function testRejectHuge()
    {
        // FIXME: huge.dat is rejected for other reasons; manual testing shows that this works
        file_put_contents("data/huge.jpg", file_get_contents("tests/pbx_screenshot.jpg") . str_repeat("U", 1024*1024*3));
        try {
            $this->post_image("data/huge.jpg", "test");
            $this->assertTrue(false, "Uploading huge.jpg didn't fail...");
        } catch (UploadException $e) {
            $this->assertEquals("File too large (3.0MB > 1.0MB)", $e->error);
        }
        unlink("data/huge.jpg");
    }
}
