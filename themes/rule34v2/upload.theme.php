<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;

class CustomUploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        $page->add_block(new Block("Upload", $this->build_upload_block(), "head", 20));
        $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    public function display_full(Page $page): void
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "head", 20));
    }

    public function display_page(Page $page): void
    {
        parent::display_page($page);
        $html = "
			<a href='//rule34.paheal.net/wiki/tagging'>Tagging Guide</a>
		";
        $page->add_block(new Block(null, $html, "main", 19));
    }

    protected function build_upload_block(): HTMLElement
    {
        return A(["href"=>make_link("upload"), "style"=>'font-size: 2em; display: block;'], "Upload");
    }
}
