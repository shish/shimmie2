<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class IcoFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        $ilink = $image->get_image_link();
        $html = IMG([
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'src' => $ilink,
            'alt' => 'main image',
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
        Ctx::$page->add_block(new Block(null, $html, "main", 10));
    }
}
