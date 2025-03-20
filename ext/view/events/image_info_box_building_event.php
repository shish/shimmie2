<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class ImageInfoBoxBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public Image $image,
        public User $user
    ) {
        parent::__construct();
    }
}
