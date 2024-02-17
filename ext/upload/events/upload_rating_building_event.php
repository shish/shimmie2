<?php

declare(strict_types=1);

namespace Shimmie2;

class UploadRatingBuildingEvent extends Event
{
    public string $part;
    public string $suffix;

    public function __construct(string $suffix)
    {
        parent::__construct();

        $this->suffix = $suffix;
    }
}
