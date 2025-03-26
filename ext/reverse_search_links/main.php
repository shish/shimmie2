<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReverseSearchLinks extends Extension
{
    public const KEY = "reverse_search_links";
    /** @var ReverseSearchLinksTheme */
    protected Themelet $theme;

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        // only support image types
        $supported_types = [MimeType::JPEG, MimeType::GIF, MimeType::PNG, MimeType::WEBP];
        if (in_array($event->image->get_mime(), $supported_types)) {
            $this->theme->reverse_search_block($event->image);
        }
    }
}
