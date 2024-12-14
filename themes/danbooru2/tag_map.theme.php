<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class Danbooru2TagMapTheme extends TagMapTheme
{
    public function display_page(string $heading, HTMLElement $list): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_page($heading, $list);
    }
}
