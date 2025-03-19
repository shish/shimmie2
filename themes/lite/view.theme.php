<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class LiteViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_title("Post {$image->id}: ".$image->get_tag_list());
        $page->set_heading($image->get_tag_list());
        $page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
        $page->add_block(new Block("Statistics", $this->build_stats($image), "left", 15));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 11));
        $page->add_block(new Block(null, $this->build_pin($image), "main", 11));
    }
}
