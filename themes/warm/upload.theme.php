<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomUploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        $page->add_block(new Block("Upload", $this->build_upload_block(), "head", 20));
    }

    public function display_full(Page $page): void
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "head", 20));
    }
}
