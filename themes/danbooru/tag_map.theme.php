<?php

declare(strict_types=1);

namespace Shimmie2;

class DanbooruTagMapTheme extends TagMapTheme
{
    protected function display_nav(): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_nav();
    }
}
