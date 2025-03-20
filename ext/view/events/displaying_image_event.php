<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * DisplayingImageEvent:
 *   $image -- the image being displayed
 *   $page  -- the page to display on
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
class DisplayingImageEvent extends Event
{
    public function __construct(
        public Image $image
    ) {
        parent::__construct();
    }
}
