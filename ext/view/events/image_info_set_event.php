<?php declare(strict_types=1);

class ImageInfoSetEvent extends Event
{
    /** @var Image */
    public $image;

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}
