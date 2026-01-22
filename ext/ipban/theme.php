<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, emptyHTML};

use MicroHTML\HTMLElement;

class IPBanTheme extends Themelet
{
    public function display_bans(HTMLElement $table, HTMLElement $paginator): void
    {
        Ctx::$page->set_title("IP Bans");
        Ctx::$page->add_to_navigation(emptyHTML(
            BR(),
            A(["href" => make_link("ip_ban/list", ["r__size" => "1000000"])], "Show All Active"),
            BR(),
            A(["href" => make_link("ip_ban/list", ["r_all" => "on", "r__size" => "1000000"])], "Show EVERYTHING")
        ));
        Ctx::$page->add_block(new Block(null, emptyHTML(
            $table,
            $paginator
        )));
    }
}
