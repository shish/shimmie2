<?php

class DisplayingImageEvent extends Event
{
    /** @var Image  */
    public $image;

    public $title;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    public function get_image(): Image
    {
        return $this->image;
    }

    public function set_title(String $title)
    {
        $this->title = $title;
    }
}
