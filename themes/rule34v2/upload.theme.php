<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\TABLE;
use function MicroHTML\TR;
use function MicroHTML\TD;
use function MicroHTML\SMALL;
use function MicroHTML\rawHTML;
use function MicroHTML\INPUT;
use function MicroHTML\emptyHTML;
use function MicroHTML\NOSCRIPT;
use function MicroHTML\DIV;
use function MicroHTML\BR;
use function MicroHTML\A;
use function MicroHTML\P;

class CustomUploadTheme extends UploadTheme
{
    // override to put upload block in head and left
    // (with css media queries deciding which one is visible)
    public function display_block(Page $page): void
    {
        $page->add_block(new Block("Upload", $this->build_upload_block(), "head", 20));
        $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    // override to put the warning in the header
    public function display_full(Page $page): void
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "head", 20));
    }

    // override to remove small uploader and just show a link to
    // the big page
    protected function build_upload_block(): HTMLElement
    {
        return A(["href" => make_link("upload"), "style" => 'font-size: 1.7rem; display: block;'], "Upload");
    }
}
