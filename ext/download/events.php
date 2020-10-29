<?php

class ImageDownloadingEvent extends Event
{
    public $image;
    public $mime;
    public $path;
    public $file_modified = false;

    public function __construct(Image $image, String $path, string $mime)
    {
        parent::__construct();
        $this->image = $image;
        $this->path = $path;
        $this->mime = $mime;
    }
}
