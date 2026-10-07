<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZoomToFitConfig extends ConfigGroup
{
    public const KEY = "zoom_to_fit";

    #[ConfigMeta(
        "Click to Toggle Zoom",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "Allow clicking on the image to toggle between Full and Fit Width zoom levels (Will conflict with any other click handlers)",
    )]
    public const CLICK_TO_TOGGLE = "zoom_to_fit_click_to_toggle";
}
