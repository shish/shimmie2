<?php

class PostTitleSetEvent extends Event
{
    public $image;
    public $title;

    public function __construct(Image $image, String $title)
    {
        $this->image = $image;
        $this->title = $title;
    }
}
