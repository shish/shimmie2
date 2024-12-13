<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class DanbooruTagListTheme extends TagListTheme
{
    public function display_page(HTMLElement $list): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_page($list);
    }
}
