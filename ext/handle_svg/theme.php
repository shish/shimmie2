<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class SVGFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $page;
        $html = IMG([
            'alt' => 'main image',
            'src' => make_link("get_svg/{$image->id}/{$image->id}.svg"),
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
        $page->add_block(new Block(null, $html, "main", 10));
    }
}
