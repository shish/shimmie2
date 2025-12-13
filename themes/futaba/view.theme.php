<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class FutabaViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     * @param HTMLElement[] $sidebar_parts
     */
    public function display_page(Image $image, array $editor_parts, array $sidebar_parts): void
    {
        Ctx::$page->set_heading($image->get_tag_list());
        Ctx::$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
    }
}
