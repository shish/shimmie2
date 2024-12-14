<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, rawHTML, joinHTML};

class TagMapTheme extends Themelet
{
    public function display_page(string $heading, HTMLElement $list): void
    {
        global $page;

        $page->set_title("Tag List");
        $page->set_heading($heading);
        $page->add_block(new Block("Tags", $list));

        $nav = joinHTML(
            BR(),
            [
                A(["href" => make_link()], "Index"),
                rawHTML("&nbsp;"),
                A(["href" => make_link("tags/map")], "Map"),
                A(["href" => make_link("tags/alphabetic")], "Alphabetic"),
                A(["href" => make_link("tags/popularity")], "Popularity"),
                rawHTML("&nbsp;"),
                A(["href" => modify_current_url(["mincount" => 1])], "Show All"),
            ]
        );

        $page->add_block(new Block("Navigation", $nav, "left", 0));
    }
}
