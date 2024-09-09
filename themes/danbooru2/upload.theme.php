<?php

declare(strict_types=1);

namespace Shimmie2;

class Danbooru2UploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        // this theme links to /upload
        // $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    public function display_page(Page $page): void
    {
        $page->set_layout("no-left");
        parent::display_page($page);
    }
}
