<?php

declare(strict_types=1);

namespace Shimmie2;

class TermsTest extends ShimmiePHPUnitTestCase
{
    public function testFresh(): void
    {
        $this->request('GET', 'post/list', cookies: []);
        self::assert_text("terms-modal-enter");
    }

    public function testLoggedIn(): void
    {
        $this->log_in_as_user();
        $this->request('GET', 'post/list', cookies: []);
        self::assert_no_text("terms-modal-enter");
    }

    public function testCookie(): void
    {
        $this->request('GET', 'post/list', cookies: ['shm_accepted_terms' => 'true']);
        self::assert_no_text("terms-modal-enter");
    }

    public function testWiki(): void
    {
        $this->request('GET', 'wiki/rules');
        self::assert_no_text("terms-modal-enter");
    }

    public function testAcceptTerms(): void
    {
        $page = $this->request('POST', 'accept_terms/post/list');
        self::assertEquals($page->mode, PageMode::REDIRECT);
        self::assertEquals($page->redirect, make_link('post/list'));

        $page = $this->request('POST', 'accept_terms/');
        self::assertEquals($page->mode, PageMode::REDIRECT);
        self::assertEquals($page->redirect, make_link(''));
    }
}
