<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArtistsTest extends ShimmiePHPUnitTestCase
{
    public function testSearch(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $image = Image::by_id_ex($image_id);

        send_event(new AuthorSetEvent($image, Ctx::$user, "bob"));

        self::assert_search_results(["author=bob"], [$image_id]);
    }
}
