<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReplaceFileTest extends ShimmiePHPUnitTestCase
{
    public function testReplacePage(): void
    {
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::get_page("replace/$image_id");
        self::assert_title("Replace File");
    }
    public function testReplace(): void
    {
        global $database;
        self::log_in_as_admin();

        // upload an image
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        // check that the image is original
        $image = Image::by_id_ex($image_id);
        $old_hash = \Safe\md5_file("tests/pbx_screenshot.jpg");
        //self::assertEquals("pbx_screenshot.jpg", $image->filename);
        self::assertEquals("image/jpeg", $image->get_mime());
        self::assertEquals(19774, $image->filesize);
        self::assertEquals(640, $image->width);
        self::assertEquals($old_hash, $image->hash);

        // replace it
        // create a copy because the file is deleted after upload
        $tmpfile = shm_tempnam("test");
        (new Path("tests/favicon.png"))->copy($tmpfile);
        $new_hash = $tmpfile->md5();
        $_FILES = [
            'data' => [
                'name' => 'favicon.png',
                'type' => 'image/png',
                'tmp_name' => $tmpfile->str(),
                'error' => 0,
                'size' => 246,
            ]
        ];
        $page = self::post_page("replace/$image_id");
        self::assert_response(302);
        self::assertEquals("/test/post/view/$image_id", $page->redirect);

        // check that there's still one image
        self::assertEquals(1, $database->get_one("SELECT COUNT(*) FROM images"));

        // check that the image was replaced
        $image = Image::by_id_ex($image_id);
        // self::assertEquals("favicon.png", $image->filename); // TODO should we update filename?
        self::assertEquals("image/png", $image->get_mime());
        self::assertEquals(246, $image->filesize);
        self::assertEquals(16, $image->width);
        self::assertEquals(md5_file("tests/favicon.png"), $image->hash);

        // check that new files exist and old files don't
        self::assertFalse(Filesystem::warehouse_path(Image::IMAGE_DIR, $old_hash)->exists());
        self::assertFalse(Filesystem::warehouse_path(Image::THUMBNAIL_DIR, $old_hash)->exists());
        self::assertTrue(Filesystem::warehouse_path(Image::IMAGE_DIR, $new_hash)->exists());
        self::assertTrue(Filesystem::warehouse_path(Image::THUMBNAIL_DIR, $new_hash)->exists());
    }
}
