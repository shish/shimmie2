<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;

class IPBanTheme extends Themelet
{
    public function display_bans(Page $page, HTMLElement $table, HTMLElement $paginator): void
    {
        $html = "
			<a href='".make_link("ip_ban/list", "r__size=1000000")."'>Show All Active</a> /
			<a href='".make_link("ip_ban/list", "r_all=on&r__size=1000000")."'>Show EVERYTHING</a>
			
			$table
			
			$paginator
		";
        $page->set_title("IP Bans");
        $page->set_heading("IP Bans");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit IP Bans", $html));
    }
}
