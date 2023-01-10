<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageInfoSetEvent extends Event
{
    public Image $image;

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}
