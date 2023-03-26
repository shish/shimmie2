<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomUploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        // this theme links to /upload
        // $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    public function display_page(Page $page): void
    {
        $page->disable_left();
        parent::display_page($page);
    }
}
