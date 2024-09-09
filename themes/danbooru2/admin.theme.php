<?php

declare(strict_types=1);

namespace Shimmie2;

class Danbooru2AdminPageTheme extends AdminPageTheme
{
    public function display_page(): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_page();
    }
}
