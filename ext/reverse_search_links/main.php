<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<ReverseSearchLinksTheme> */
final class ReverseSearchLinks extends Extension
{
    public const KEY = "reverse_search_links";

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        // only support image types
        $supported_types = [MimeType::JPEG, MimeType::GIF, MimeType::PNG, MimeType::WEBP];
        if (MimeType::matches_array($event->image->get_mime(), $supported_types)) {
            $this->theme->reverse_search_block($event->image);
        }
    }
}
