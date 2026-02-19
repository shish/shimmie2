<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Request a thumbnail be made for an image object.
 */
final class ThumbnailGenerationEvent extends Event
{
    public bool $generated;

    /**
     * Request a thumbnail be made for an image object
     */
    public function __construct(
        public readonly Post $image,
        public readonly bool $force = false
    ) {
        parent::__construct();
        $this->generated = false;
    }
}
