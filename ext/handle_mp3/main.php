<?php

declare(strict_types=1);

namespace Shimmie2;

// TODO: Add support for generating an icon from embedded cover art
// TODO: MORE AUDIO FORMATS

class MP3FileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::MP3];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->audio = true;
        $event->image->video = false;
        $event->image->lossless = false;
        $event->image->image = false;
        $event->image->width = 0;
        $event->image->height = 0;
        // TODO: ->length = ???
    }

    protected function create_thumb(Image $image): bool
    {
        copy("ext/handle_mp3/thumb.jpg", $image->get_thumb_filename());
        return true;
    }

    protected function check_contents(string $tmpname): bool
    {
        return MimeType::get_for_file($tmpname) === MimeType::MP3;
    }
}
