<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * An image is being replaced.
 */
final class ImageReplaceEvent extends Event
{
    /** @var non-empty-string */
    public string $old_hash;
    /** @var non-empty-string */
    public string $new_hash;

    /**
     * Replaces an image file.
     *
     * Updates an existing ID in the database to use a new image
     * file, leaving the tags and such unchanged. Also removes
     * the old image file and thumbnail from the disk.
     */
    public function __construct(
        public Image $image,
        public Path $tmp_filename,
    ) {
        parent::__construct();
        $this->old_hash = $image->hash;
        $this->new_hash = $tmp_filename->md5();
    }
}
