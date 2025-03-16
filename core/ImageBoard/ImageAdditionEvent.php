<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * An image is being added to the database.
 */
final class ImageAdditionEvent extends Event
{
    /**
     * A new image is being added to the database - just the image,
     * metadata will come later with ImageInfoSetEvent (and if that
     * fails, then the image addition transaction will be rolled back)
     */
    public function __construct(
        public Image $image,
    ) {
        parent::__construct();
    }
}
