<?php

declare(strict_types=1);

namespace Shimmie2;

class PostTitleSetEvent extends Event
{
    public Image $image;
    public string $title;

    public function __construct(Image $image, string $title)
    {
        parent::__construct();
        $this->image = $image;
        $this->title = $title;
    }
}
