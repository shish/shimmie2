<?php

declare(strict_types=1);

namespace Shimmie2;

class ResizeConfig extends ConfigGroup
{
    public ?string $title = "Image Resize";

    #[ConfigMeta("Resize engine", ConfigType::STRING, options: [
        'GD' => 'gd',
        'ImageMagick' => 'convert'
    ])]
    public const ENGINE = 'resize_engine';

    #[ConfigMeta("Allow manual resizing", ConfigType::BOOL)]
    public const ENABLED = 'resize_enabled';

    #[ConfigMeta("Allow GET args", ConfigType::BOOL)]
    public const GET_ENABLED = 'resize_get_enabled';

    #[ConfigMeta("Resize on upload", ConfigType::BOOL)]
    public const UPLOAD = 'resize_upload';

    #[ConfigMeta("Default Width (px)", ConfigType::INT)]
    public const DEFAULT_WIDTH = 'resize_default_width';

    #[ConfigMeta("Default Height (px)", ConfigType::INT)]
    public const DEFAULT_HEIGHT = 'resize_default_height';

}
