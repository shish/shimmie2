<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermsTest extends ShimmiePHPUnitTestCase
{
    public function testFresh(): void
    {
        self::request('GET', 'post/list', cookies: []);
        self::assert_text("terms-modal-enter");
    }

    public function testLoggedIn(): void
    {
        self::log_in_as_user();
        self::request('GET', 'post/list', cookies: []);
        self::assert_no_text("terms-modal-enter");
    }

    public function testCookie(): void
    {
        self::request('GET', 'post/list', cookies: ['shm_accepted_terms' => 'true']);
        self::assert_no_text("terms-modal-enter");
    }

    public function testWiki(): void
    {
        self::request('GET', 'wiki/rules');
        self::assert_no_text("terms-modal-enter");
    }

    public function testAcceptTerms(): void
    {
        $page = self::request('POST', 'accept_terms/post/list');
        self::assertEquals($page->mode, PageMode::REDIRECT);
        self::assertEquals($page->redirect, make_link('post/list'));

        $page = self::request('POST', 'accept_terms/');
        self::assertEquals($page->mode, PageMode::REDIRECT);
        self::assertEquals($page->redirect, make_link(''));
    }
}
