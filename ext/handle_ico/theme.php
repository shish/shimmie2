<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class IcoFileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        return IMG([
            'src' => $image->get_image_link(),
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'alt' => 'main image',
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
    }
}
