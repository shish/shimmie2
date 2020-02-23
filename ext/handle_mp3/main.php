<?php declare(strict_types=1);

class MP3FileHandler extends DataHandlerExtension
{
    protected $SUPPORTED_EXT = ["mp3"];

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

    protected function create_thumb(string $hash, string $type): bool
    {
        copy("ext/handle_mp3/thumb.jpg", warehouse_path(Image::THUMBNAIL_DIR, $hash));
        return true;
    }

    protected function check_contents(string $tmpname): bool
    {
        return getMimeType($tmpname) == 'audio/mpeg';
    }
}
