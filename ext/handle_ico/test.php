<?php

declare(strict_types=1);

namespace Shimmie2;

final class IcoFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testIcoHander(): void
    {
        // By default ExtraImageFileHandler converts ICO to PNG
        Ctx::$config->set(
            ExtraImageFileHandler::get_mapping_name(new MimeType(MimeType::ICO)),
            "-"
        );

        self::log_in_as_user();
        $image_id = $this->post_image("ext/static_files/static/favicon.ico", "shimmie favicon");

        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);

        $image = Image::by_id_ex($image_id);
        self::assertEquals("ico", $image->get_ext());

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }
}
