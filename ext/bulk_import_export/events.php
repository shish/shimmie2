<?php declare(strict_types=1);

class BulkExportEvent extends Event
{
    public $image;
    public $fields = [];

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}


class BulkImportEvent extends Event
{
    public $image;
    public $fields = [];

    public function __construct(Image $image, $fields)
    {
        parent::__construct();
        $this->image = $image;
        $this->fields = $fields;
    }
}
