<?php

declare(strict_types=1);

namespace Shimmie2;

class HelpPagesTheme extends Themelet
{
    /**
     * @param array<string,string> $pages
     */
    public function display_list_page(array $pages): void
    {
        global $page;

        $page->set_title("Help Pages");
        $page->set_heading("Help Pages");

        $nav_block = new Block("Help", "", "left", 0);
        foreach ($pages as $link => $desc) {
            $link = make_link("help/{$link}");
            $nav_block->body .= "<a href='{$link}'>".html_escape($desc)."</a><br/>";
        }

        $page->add_block($nav_block);
        $page->add_block(new Block("Help Pages", "See list of pages to left"));
    }

    public function display_help_page(string $title): void
    {
        global $page;

        $page->set_title("Help - $title");
        $page->set_heading("Help - $title");
        $page->add_block(new NavBlock());
    }
}
