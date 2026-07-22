<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class MicroHTMLTest extends TestCase
{
    public function test_date(): void
    {
        self::assertSame(
            "<time datetime='2012-06-23T16:14:22+00:00'>June 23, 2012; 16:14</time>",
            (string)SHM_DATE("2012-06-23 16:14:22")
        );
    }

    /**
     * Browsers throw away a GET form's action query string and replace it
     * with the form fields, so uglyurl installs need the page repeating as
     * a hidden field, see #2131
     */
    public function test_get_form_page(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertSame(
            "<form action='/test/index.php?q=post%2Flist%2F1' method='GET'>" .
            "<input type='hidden' name='q' value='post/list/1' /></form>",
            (string)SHM_FORM(action: search_link(), method: "GET")
        );

        // with niceurls the action path carries the page by itself
        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertSame(
            "<form action='/test/post/list/1' method='GET'></form>",
            (string)SHM_FORM(action: search_link(), method: "GET")
        );
    }
}
