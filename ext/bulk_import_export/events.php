<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkExportEvent extends Event
{
    public Image $image;
    /** @var array<string,mixed> */
    public array $fields = [];

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}


class BulkImportEvent extends Event
{
    public Image $image;
    /** @var array<string,mixed> */
    public array $fields = [];

    /**
     * @param array<string,mixed> $fields
     */
    public function __construct(Image $image, array $fields)
    {
        parent::__construct();
        $this->image = $image;
        $this->fields = $fields;
    }
}
