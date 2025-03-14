<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageDownloadingEvent extends Event
{
    public bool $file_modified = false;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public Image $image,
        public Path $path,
        public string $mime,
        public array $params
    ) {
        parent::__construct();
    }
}
