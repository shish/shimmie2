<?php

declare(strict_types=1);

namespace Shimmie2;

class RotateImageTheme extends Themelet
{
    /**
     * Display the error.
     */
    public function display_rotate_error(Page $page, string $title, string $message): void
    {
        $page->set_title("Rotate Image");
        $page->set_heading("Rotate Image");
        $page->add_block(new NavBlock());
        $page->add_block(new Block($title, $message));
    }
}
