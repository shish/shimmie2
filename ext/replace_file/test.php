<?php

declare(strict_types=1);

namespace Shimmie2;

class ReplaceFileTest extends ShimmiePHPUnitTestCase
{
    public function testReplacePage(): void
    {
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->get_page("replace/$image_id");
        $this->assert_title("Replace File");
    }
    public function testReplace(): void
    {
        global $database;
        $this->log_in_as_admin();

        // upload an image
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        // check that the image is original
        $image = Image::by_id_ex($image_id);
        $old_hash = \Safe\md5_file("tests/pbx_screenshot.jpg");
        //$this->assertEquals("pbx_screenshot.jpg", $image->filename);
        $this->assertEquals("image/jpeg", $image->get_mime());
        $this->assertEquals(19774, $image->filesize);
        $this->assertEquals(640, $image->width);
        $this->assertEquals($old_hash, $image->hash);

        // replace it
        // create a copy because the file is deleted after upload
        $tmpfile = shm_tempnam("test");
        copy("tests/favicon.png", $tmpfile);
        $new_hash = \Safe\md5_file($tmpfile);
        $_FILES = [
            'data' => [
                'name' => 'favicon.png',
                'type' => 'image/png',
                'tmp_name' => $tmpfile,
                'error' => 0,
                'size' => 246,
            ]
        ];
        $page = $this->post_page("replace/$image_id");
        $this->assert_response(302);
        $this->assertEquals("/test/post/view/$image_id", $page->redirect);

        // check that there's still one image
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM images"));

        // check that the image was replaced
        $image = Image::by_id_ex($image_id);
        // $this->assertEquals("favicon.png", $image->filename); // TODO should we update filename?
        $this->assertEquals("image/png", $image->get_mime());
        $this->assertEquals(246, $image->filesize);
        $this->assertEquals(16, $image->width);
        $this->assertEquals(md5_file("tests/favicon.png"), $image->hash);

        // check that new files exist and old files don't
        $this->assertFalse(file_exists(warehouse_path(Image::IMAGE_DIR, $old_hash)));
        $this->assertFalse(file_exists(warehouse_path(Image::THUMBNAIL_DIR, $old_hash)));
        $this->assertTrue(file_exists(warehouse_path(Image::IMAGE_DIR, $new_hash)));
        $this->assertTrue(file_exists(warehouse_path(Image::THUMBNAIL_DIR, $new_hash)));
    }
}
