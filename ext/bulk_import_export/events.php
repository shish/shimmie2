<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Signal that a given image is being exported, requesting
 * any extra metadata to be included in the export.
 */
class BulkExportEvent extends Event
{
    public Image $image;
    /**
     * Arbitrary data to be included in the export JSON.
     * @var array<string, mixed>
     */
    public array $fields = [];

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}


class BulkImportEvent extends Event
{
    public function __construct(
        public Image $image,
        public \stdClass $fields
    ) {
        parent::__construct();
    }
}
