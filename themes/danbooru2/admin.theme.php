<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomAdminPageTheme extends AdminPageTheme
{
    public function display_page(): void
    {
        global $page;
        $page->disable_left();
        parent::display_page();
    }
}
