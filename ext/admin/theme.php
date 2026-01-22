<?php

declare(strict_types=1);

namespace Shimmie2;

class AdminPageTheme extends Themelet
{
    /*
     * Show the basics of a page, for other extensions to add to
     */
    public function display_page(): void
    {
        Ctx::$page->set_title("Admin Tools");
    }
}
