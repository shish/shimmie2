<?php declare(strict_types=1);

class FlashFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::FLASH];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = true;
        $event->image->video = true;
        $event->image->image = false;

        $info = getimagesize($event->file_name);
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
    }

    protected function create_thumb(string $hash, string $mime): bool
    {
        if (!Media::create_thumbnail_ffmpeg($hash)) {
            copy("ext/handle_flash/thumb.jpg", warehouse_path(Image::THUMBNAIL_DIR, $hash));
        }
        return true;
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = fopen($tmpname, "r");
        $head = fread($fp, 3);
        fclose($fp);
        return in_array($head, ["CWS", "FWS"]);
    }
}
