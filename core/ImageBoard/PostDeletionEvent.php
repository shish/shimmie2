<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * An image is being deleted.
 */
final class PostDeletionEvent extends Event
{
    /**
     * Deletes an image.
     *
     * Used by things like tags and comments handlers to
     * clean out related rows in their tables.
     */
    public function __construct(
        public readonly Post $image,
        public readonly bool $force = false,
    ) {
        parent::__construct();
    }
}
