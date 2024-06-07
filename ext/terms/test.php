<?php

declare(strict_types=1);

namespace Shimmie2;

class TermsTest extends ShimmiePHPUnitTestCase
{
    public function testFresh(): void
    {
        $this->request('GET', 'post/list', cookies: []);
        $this->assert_text("terms-modal-enter");
    }

    public function testLoggedIn(): void
    {
        $this->log_in_as_user();
        $this->request('GET', 'post/list', cookies: []);
        $this->assert_no_text("terms-modal-enter");
    }

    public function testCookie(): void
    {
        $this->request('GET', 'post/list', cookies: ['shm_accepted_terms' => 'true']);
        $this->assert_no_text("terms-modal-enter");
    }

    public function testWiki(): void
    {
        $this->request('GET', 'wiki/rules');
        $this->assert_no_text("terms-modal-enter");
    }

    public function testAcceptTerms(): void
    {
        $page = $this->request('POST', 'accept_terms/post/list');
        $this->assertEquals($page->mode, PageMode::REDIRECT);
        $this->assertEquals($page->redirect, make_link('post/list'));

        $page = $this->request('POST', 'accept_terms/');
        $this->assertEquals($page->mode, PageMode::REDIRECT);
        $this->assertEquals($page->redirect, make_link(''));
    }
}
