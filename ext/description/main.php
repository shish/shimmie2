<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageDescription extends Extension
{
    public const KEY = "image_description";

    /** @var ImageDescriptionsTheme */
    protected Themelet $theme;
}
