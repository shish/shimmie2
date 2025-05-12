<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageDescription extends Extension
{
    public const KEY = "image_description";

    /** @var ImageDescriptionsTheme */
    protected Themelet $theme;

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_description_editor_html($event->image), 35);
    }
}
