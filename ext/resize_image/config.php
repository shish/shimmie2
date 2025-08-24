<?php

declare(strict_types=1);

namespace Shimmie2;

final class ResizeConfig extends ConfigGroup
{
    public const KEY = "resize";
    public ?string $title = "Image Resize";

    #[ConfigMeta("Resize engine", ConfigType::STRING, default: 'gd', options: [
        'GD' => 'gd',
        'ImageMagick' => 'convert'
    ])]
    public const ENGINE = 'resize_engine';

    #[ConfigMeta("Allow manual resizing", ConfigType::BOOL, default: true)]
    public const ENABLED = 'resize_enabled';

    #[ConfigMeta("Allow GET args", ConfigType::BOOL, default: false)]
    public const GET_ENABLED = 'resize_get_enabled';

    #[ConfigMeta("Resize on upload", ConfigType::BOOL, default: false)]
    public const UPLOAD = 'resize_upload';

    #[ConfigMeta("Default width (px)", ConfigType::INT, default: 0)]
    public const DEFAULT_WIDTH = 'resize_default_width';

    #[ConfigMeta("Default height (px)", ConfigType::INT, default: 0)]
    public const DEFAULT_HEIGHT = 'resize_default_height';
}
