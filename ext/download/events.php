<?php

class ImageDownloadingEvent extends Event
{
    public Image $image;
    public string $mime;
    public string $path;
    public bool $file_modified = false;

    public function __construct(Image $image, string $path, string $mime)
    {
        parent::__construct();
        $this->image = $image;
        $this->path = $path;
        $this->mime = $mime;
    }
}
