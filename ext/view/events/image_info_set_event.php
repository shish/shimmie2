<?php

class ImageInfoSetEvent extends Event
{
    /** @var Image */
    public $image;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }
}
