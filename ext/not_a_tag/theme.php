<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;

class NotATagTheme extends Themelet
{
    public function display_untags(Page $page, HTMLElement $table, HTMLElement $paginator): void
    {
        $page->set_title("UnTags");
        $page->set_heading("UnTags");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit UnTags", emptyHTML($table, $paginator)));
    }
}
