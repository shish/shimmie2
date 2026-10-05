<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{FORM, OPTION, SELECT};

final class ZoomToFit extends Extension
{
    public const KEY = "zoom_to_fit";

    #[EventListener]
    public function onPostAdminBlockBuilding(PostAdminBlockBuildingEvent $event): void
    {
        if (str_starts_with($event->image->get_mime()->base, "image/")) {
            if ($event->context === "view") {
                $click_to_toggle = Ctx::$config->get(ZoomToFitConfig::CLICK_TO_TOGGLE);
                $event->add_part(FORM(
                    SELECT(
                        [
                            "id" => "shm-zoomer",
                            "data-click-to-toggle" => $click_to_toggle ? "true" : "false",
                        ],
                        OPTION(["value" => "full"], "Full Size"),
                        OPTION(["value" => "width"], "Fit Width"),
                        OPTION(["value" => "both"], "Fit Both"),
                    )
                ), 19);
            }
        }
    }
}
