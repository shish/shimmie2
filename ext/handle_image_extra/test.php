<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraImageFileHandlerTest extends ShimmiePHPUnitTestCase
{
    // ExtraImageFileHandler should be able to support all standard image
    // formats, plus some extras.
    public function testSuperset(): void
    {
        $standard = ImageFileHandler::SUPPORTED_MIME;
        $extra = array_values(ExtraImageFileHandler::INPUT_MIMES);
        foreach ($standard as $mime) {
            self::assertContains($mime, $extra, "ExtraImageFileHandler should support all standard image formats");
        }
    }

    public function testNonStandardImage(): void
    {
        // create a .bmp image
        $tmp = shm_tempnam("test-format");
        $img = \Safe\imagecreatefromstring(\Safe\file_get_contents("tests/pbx_screenshot.jpg"));
        self::assertNotFalse($img);
        \Safe\imagebmp($img, $tmp->str().".bmp");

        // check that it can be uploaded
        self::log_in_as_user();
        $image_id = $this->post_image($tmp->str().".bmp", "pbx computer screenshot");
        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);

        // check that it was converted to png
        $image = Image::by_id_ex($image_id);
        self::assertEquals("image/png", $image->get_mime());
    }
}
