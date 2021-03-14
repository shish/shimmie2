<?php declare(strict_types=1);

class ImageInfoSetEvent extends Event
{
    public Image $image;

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}
