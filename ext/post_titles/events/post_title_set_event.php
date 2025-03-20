<?php

declare(strict_types=1);

namespace Shimmie2;

class PostTitleSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public string $title
    ) {
        parent::__construct();
    }
}
