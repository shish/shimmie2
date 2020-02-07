<?php

class BulkExportEvent extends Event
{
    public $image;
    public $fields = [];

    public function __construct(Image $image)
    {
        $this->image = $image;
    }
}


class BulkImportEvent extends Event
{
    public $image;

    public function __construct(Image $image, $fields)
    {
        $this->image = $image;
        $this->fields = $fields;
    }
}
