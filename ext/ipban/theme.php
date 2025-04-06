<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, emptyHTML};

use MicroHTML\HTMLElement;

class IPBanTheme extends Themelet
{
    public function display_bans(HTMLElement $table, HTMLElement $paginator): void
    {
        Ctx::$page->set_title("IP Bans");
        $this->display_navigation();
        Ctx::$page->add_block(new Block(null, emptyHTML(
            A(["href" => make_link("ip_ban/list", ["r__size" => "1000000"])], "Show All Active"),
            A(["href" => make_link("ip_ban/list", ["r_all" => "on", "r__size" => "1000000"])], "Show EVERYTHING"),
            $table,
            $paginator
        )));
    }
}
