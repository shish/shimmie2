<?php declare(strict_types=1);

class MP3FileHandler extends DataHandlerExtension
{
    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        switch ($event->ext) {
            case "mp3":
                $event->image->audio = true;
                $event->image->video = false;
                $event->image->lossless = false;
                $event->image->image = false;
                $event->image->width = 0;
                $event->image->height = 0;
                break;
        }
        // TODO: Buff out audio format support, length scanning
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        copy("ext/handle_mp3/thumb.jpg", warehouse_path(Image::THUMBNAIL_DIR, $hash));
        return true;
    }

    protected function supported_ext(string $ext): bool
    {
        $exts = ["mp3"];
        return in_array(strtolower($ext), $exts);
    }

    protected function check_contents(string $tmpname): bool
    {
        return getMimeType($tmpname) == 'audio/mpeg';
    }
}
