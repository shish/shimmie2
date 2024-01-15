<?php

declare(strict_types=1);

namespace Shimmie2;

class QRImage extends Extension
{
    /** @var QRImageTheme */
    protected Themelet $theme;

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $this->theme->links_block(make_http(make_link('image/'.$event->image->id.'.'.$event->image->get_ext())));
    }
}
