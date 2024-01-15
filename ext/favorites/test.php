<?php

declare(strict_types=1);

namespace Shimmie2;

class FavoritesTest extends ShimmiePHPUnitTestCase
{
    public function testFavorites(): void
    {
        global $user;
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        # No favourites
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: test");
        $this->assert_no_text("Favorited By");

        # Add a favourite
        send_event(new FavoriteSetEvent($image_id, $user, true));

        # Favourite shown on page
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: test");
        $this->assert_text("Favorited By");

        # Favourite shown on index
        $page = $this->get_page("post/list/favorited_by=test/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        # Favourite shown on user page
        $this->get_page("user/test");
        $this->assert_text("Posts favorited</a>: 1");

        # Delete a favourite
        send_event(new FavoriteSetEvent($image_id, $user, false));

        # No favourites
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: test");
        $this->assert_no_text("Favorited By");
    }
}
