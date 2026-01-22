<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class LiteViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     * @param HTMLElement[] $sidebar_parts
     */
    public function display_page(Image $image, array $editor_parts, array $sidebar_parts): void
    {
        Ctx::$page->set_title("Post {$image->id}: ".$image->get_tag_list());
        Ctx::$page->set_heading($image->get_tag_list());
        Ctx::$page->add_block(new Block("Statistics", $this->build_stats($image), "left", 15));
        Ctx::$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 11));
        Ctx::$page->add_block(new Block(null, $this->build_pin($image), "main", 11));
    }
}
