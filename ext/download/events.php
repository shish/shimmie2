<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageDownloadingEvent extends Event
{
    public bool $file_modified = false;

    public function __construct(
        public Image $image,
        public Path $path,
        public MimeType $mime,
        public QueryArray $params
    ) {
        parent::__construct();
    }
}
