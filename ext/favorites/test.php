<?php

declare(strict_types=1);

namespace Shimmie2;

class FavoritesTest extends ShimmiePHPUnitTestCase
{
    public function testFavorites(): void
    {
        global $user;
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        # No favourites
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: test");
        self::assert_no_text("Favorited By");

        # Add a favourite
        send_event(new FavoriteSetEvent($image_id, $user, true));

        # Favourite shown on page
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: test");
        self::assert_text("Favorited By");

        # Favourite shown on index
        $page = self::get_page("post/list/favorited_by=test/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        # Favourite shown on user page
        self::get_page("user/test");
        self::assert_text("Posts favorited</a>: 1");

        # Delete a favourite
        send_event(new FavoriteSetEvent($image_id, $user, false));

        # No favourites
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: test");
        self::assert_no_text("Favorited By");
    }
}
