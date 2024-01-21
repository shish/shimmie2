<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class CustomViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_heading(html_escape($image->get_tag_list()));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
    }
}
