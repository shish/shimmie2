<?php

declare(strict_types=1);
class ArtistsTest extends ShimmiePHPUnitTestCase
{
    public function testSearch()
    {
        global $user;
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $image = Image::by_id($image_id);

        send_event(new AuthorSetEvent($image, $user, "bob"));

        $this->assert_search_results(["author=bob"], [$image_id]);
    }
}
