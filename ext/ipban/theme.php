<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML, A};

class IPBanTheme extends Themelet
{
    public function display_bans(Page $page, HTMLElement $table, HTMLElement $paginator): void
    {
        $page->set_title("IP Bans");
        $page->add_block(Block::nav());
        $page->add_block(new Block(null, emptyHTML(
            A(["href" => make_link("ip_ban/list", ["r__size" => "1000000"])], "Show All Active"),
            A(["href" => make_link("ip_ban/list", ["r_all" => "on", "r__size" => "1000000"])], "Show EVERYTHING"),
            $table,
            $paginator
        )));
    }
}
