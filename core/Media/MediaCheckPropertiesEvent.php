<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaCheckPropertiesEvent extends Event
{
    public function __construct(
        public Image $image
    ) {
        parent::__construct();
    }
}
