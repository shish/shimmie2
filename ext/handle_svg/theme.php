<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class SVGFileHandlerTheme extends Themelet
{
    public function build_media(Post $image): \MicroHTML\HTMLElement
    {
        return IMG([
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'alt' => 'main image',
            'src' => $image->get_media_link(),
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
    }
}
