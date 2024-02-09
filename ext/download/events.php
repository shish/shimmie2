<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageDownloadingEvent extends Event
{
    public Image $image;
    public string $mime;
    public string $path;
    public bool $file_modified = false;
    /** @var array<string, mixed> */
    public array $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(Image $image, string $path, string $mime, array $params)
    {
        parent::__construct();
        $this->image = $image;
        $this->path = $path;
        $this->mime = $mime;
        $this->params = $params;
    }
}
