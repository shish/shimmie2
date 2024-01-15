<?php

declare(strict_types=1);

namespace Shimmie2;

class FourOhFourTest extends ShimmiePHPUnitTestCase
{
    public function test404Handler(): void
    {
        $this->get_page('not/a/page');
        // most descriptive error first
        $this->assert_text("No handler could be found for the page 'not/a/page'");
        $this->assert_title('404');
        $this->assert_response(404);
    }
}
