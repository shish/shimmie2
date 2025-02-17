<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexConfig extends ConfigGroup
{
    public const KEY = "index";
    public ?string $title = "Post List";
    public ?int $position = 20;

    #[ConfigMeta("Posts per page", ConfigType::INT, default: 24)]
    public const IMAGES = "index_images";

    #[ConfigMeta("Post order", ConfigType::STRING, default: "id DESC", advanced: true)]
    public const ORDER = "index_order";
}
