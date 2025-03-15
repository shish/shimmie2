<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\DataProvider;

final class PixelFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testPixelHander(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);
        //self::assert_response(302);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }

    /**
     * @return array<array<string>>
     */
    public static function formatList(): array
    {
        return [
            ["jpeg"],
            ["png"],
            ["gif"],
            ["webp"],
            ["avif"],
        ];
    }

    #[DataProvider('formatList')]
    public function testFormats(string $format): void
    {
        $tmp = shm_tempnam("test-format");
        $tmp->unlink();
        $img = \Safe\imagecreatefromstring(\Safe\file_get_contents("tests/pbx_screenshot.jpg"));
        $encodefunc = "image$format";
        self::assertTrue(is_callable($encodefunc));
        $encodefunc($img, "{$tmp->str()}.$format");

        $image_id = $this->post_image("{$tmp->str()}.$format", "pbx computer screenshot $format");
        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        foreach (\Safe\glob("data/temp/test-format*") as $file) {
            unlink($file);
        }
    }
}
