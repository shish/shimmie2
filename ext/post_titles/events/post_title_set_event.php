<?php declare(strict_types=1);

class PostTitleSetEvent extends Event
{
    public $image;
    public $title;

    public function __construct(Image $image, String $title)
    {
        parent::__construct();
        $this->image = $image;
        $this->title = $title;
    }
}
