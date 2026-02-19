<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\IMG;

class IcoFileHandlerTheme extends Themelet
{
    public function build_media(Post $image): \MicroHTML\HTMLElement
    {
        return IMG([
            'src' => $image->get_media_link(),
            'id' => 'main_image',
            'class' => 'shm-main-image',
            'alt' => 'main image',
            'data-width' => $image->width,
            'data-height' => $image->height,
        ]);
    }
}
