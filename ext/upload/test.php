<?php

declare(strict_types=1);

namespace Shimmie2;

class UploadTest extends ShimmiePHPUnitTestCase
{
    public function testUploadPage(): void
    {
        $this->log_in_as_user();

        $this->get_page("upload");
        $this->assert_title("Upload");
    }

    // Because $this->post_image() sends the event directly
    public function testRawUpload(): void
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
        $page = $this->post_page("upload", ["tags0" => "foo bar"]);
        $this->assert_response(302);
        $this->assertEquals(4, $database->get_one("SELECT COUNT(*) FROM images"));
        // FIXME: image IDs get allocated even when transactions are rolled back,
        // so these IDs are not necessarily correct
        // $this->assertStringStartsWith("/test/post/list/id%3D4%2C3%2C2%2C1/1", $page->redirect);
    }

    public function testUpload(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->assertGreaterThan(0, $image_id);

        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: computer pbx screenshot");
    }

    public function testRejectDupe(): void
    {
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        $e = $this->assertException(UploadException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        });
        $this->assertStringContainsString("already has hash", $e->getMessage());
    }

    public function testRejectUnknownFiletype(): void
    {
        $this->expectException(\Exception::class);
        $this->post_image("index.php", "test");
    }

    public function testRejectHuge(): void
    {
        // FIXME: huge.dat is rejected for other reasons; manual testing shows that this works
        file_put_contents("data/huge.jpg", \Safe\file_get_contents("tests/pbx_screenshot.jpg") . str_repeat("U", 1024 * 1024 * 3));
        $e = $this->assertException(UploadException::class, function () {
            $this->post_image("data/huge.jpg", "test");
        });
        unlink("data/huge.jpg");
        $this->assertEquals("File too large (3.0MB > 1.0MB)", $e->getMessage());
    }
}
