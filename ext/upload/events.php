<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<string>
 */
class UploadHeaderBuildingEvent extends PartListBuildingEvent
{
}

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class UploadCommonBuildingEvent extends PartListBuildingEvent
{
}

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class UploadSpecificBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public string $suffix
    ) {
        parent::__construct();
    }
}
