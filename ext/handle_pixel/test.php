<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\DataProvider;

class PixelFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testPixelHander(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $page = $this->get_page("post/view/$image_id");
        $this->assertEquals(200, $page->code);
        //$this->assert_response(302);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }

    public static function formatList(): array
    {
        return [
            ["jpeg"],
            ["png"],
            ["gif"],
        ];
    }

    #[DataProvider('formatList')]
    public function testFormats(string $format): void
    {
        $tmp = shm_tempnam("test-format");
        unlink($tmp);
        system("convert tests/pbx_screenshot.jpg $tmp.$format");

        $image_id = $this->post_image("$tmp.$format", "pbx computer screenshot $format");
        $page = $this->get_page("post/view/$image_id");
        $this->assertEquals(200, $page->code);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        foreach (glob("data/temp/test-format*") as $file) {
            unlink($file);
        }
    }
}
