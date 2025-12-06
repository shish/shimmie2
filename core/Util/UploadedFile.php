<?php

declare(strict_types=1);

namespace Shimmie2;

final readonly class UploadedFile
{
    public string $name;
    public string $type;
    public int $size;
    public string $tmp_name;
    public int $error;
    public string $full_path;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $meta)
    {
        $this->name = $meta["name"];
        $this->type = $meta["type"];
        $this->size = $meta["size"];
        $this->tmp_name = $meta["tmp_name"];
        $this->error = $meta["error"];
        $this->full_path = $meta["full_path"];
    }
}
