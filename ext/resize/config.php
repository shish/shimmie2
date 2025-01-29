<?php

declare(strict_types=1);

namespace Shimmie2;

class ResizeConfig extends ConfigGroup
{
    public const ENABLED = 'resize_enabled';
    public const UPLOAD = 'resize_upload';
    public const ENGINE = 'resize_engine';
    public const DEFAULT_WIDTH = 'resize_default_width';
    public const DEFAULT_HEIGHT = 'resize_default_height';
    public const GET_ENABLED = 'resize_get_enabled';
}
