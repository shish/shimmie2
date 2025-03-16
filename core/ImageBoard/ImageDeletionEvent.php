<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * An image is being deleted.
 */
final class ImageDeletionEvent extends Event
{
    /**
     * Deletes an image.
     *
     * Used by things like tags and comments handlers to
     * clean out related rows in their tables.
     */
    public function __construct(
        public Image $image,
        public bool $force = false,
    ) {
        parent::__construct();
    }
}
