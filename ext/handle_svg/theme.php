<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class SVGFileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        return IMG([
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'alt' => 'main image',
            'src' => $image->get_image_link(),
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
    }
}
