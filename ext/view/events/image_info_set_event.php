<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageInfoSetEvent extends Event
{
    public Image $image;
    /** @var array<string, string> */
    public array $params;

    /**
     * @param array<string, string> $params
     */
    public function __construct(Image $image, array $params)
    {
        parent::__construct();
        $this->image = $image;
        $this->params = $params;
    }
}
