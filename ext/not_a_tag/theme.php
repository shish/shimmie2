<?php

declare(strict_types=1);
class NotATagTheme extends Themelet
{
    public function display_untags(Page $page, $table, $paginator)
    {
        $page->set_title("UnTags");
        $page->set_heading("UnTags");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit UnTags", $table . $paginator));
    }
}
