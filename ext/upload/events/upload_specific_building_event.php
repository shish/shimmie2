<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class UploadSpecificBuildingEvent extends PartListBuildingEvent
{
    public string $suffix;

    public function __construct(string $suffix)
    {
        parent::__construct();

        $this->suffix = $suffix;
    }
}
