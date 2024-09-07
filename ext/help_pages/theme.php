<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, emptyHTML, rawHTML};

class HelpPagesTheme extends Themelet
{
    /**
     * @param array<string,string> $pages
     */
    public function display_list_page(array $pages): void
    {
        global $page;

        $page->set_title("Help Pages");

        $items = emptyHTML();
        foreach ($pages as $link => $desc) {
            $items->appendChild(
                A(["href" => make_link("help/{$link}")], $desc),
                BR(),
            );
        }

        $page->add_block(new Block("Help", $items, "left", 0));
        $page->add_block(new Block("Help Pages", rawHTML("See list of pages to left")));
    }

    public function display_help_page(string $title): void
    {
        global $page;

        $page->set_title("Help - $title");
        $page->add_block(new NavBlock());
    }
}
