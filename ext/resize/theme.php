<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML};

class ResizeImageTheme extends Themelet
{
    public function display_resize_error(Page $page, string $title, string $message): void
    {
        $page->set_title("Resize Image");
        $page->set_heading("Resize Image");
        $page->add_block(new NavBlock());
        $page->add_block(new Block($title, $message));
    }
}
