<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, emptyHTML};

class HelpPagesTheme extends Themelet
{
    /**
     * @param array<string,string> $pages
     */
    public function display_help_page(string $title, array $pages): void
    {
        $links = emptyHTML(
            A(["href" => make_link()], "Index"),
            BR(),
            BR(),
        );
        foreach ($pages as $link => $desc) {
            $links->appendChild(
                A(["href" => make_link("help/{$link}")], $desc),
                BR(),
            );
        }
        Ctx::$page->set_title("Help - $title");
        Ctx::$page->add_block(new Block("Navigation", $links, "left", position: 0));
    }
}
