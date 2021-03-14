<?php declare(strict_types=1);

class IcoFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::ICO, MimeType::ANI, MimeType::WIN_BITMAP, MimeType::ICO_OSX];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = true;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = ($event->mime!= MimeType::ANI);

        $fp = fopen($event->file_name, "r");
        try {
            unpack("Snull/Stype/Scount", fread($fp, 6));
            $subheader = unpack("Cwidth/Cheight/Ccolours/Cnull/Splanes/Sbpp/Lsize/loffset", fread($fp, 16));
        } finally {
            fclose($fp);
        }

        $width = $subheader['width'];
        $height = $subheader['height'];
        $event->image->width = $width == 0 ? 256 : $width;
        $event->image->height = $height == 0 ? 256 : $height;
    }

    protected function create_thumb(string $hash, string $mime): bool
    {
        try {
            create_image_thumb($hash, $mime, MediaEngine::IMAGICK);
            return true;
        } catch (MediaException $e) {
            log_warning("handle_ico", "Could not generate thumbnail. " . $e->getMessage());
            return false;
        }
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = fopen($tmpname, "r");
        $header = unpack("Snull/Stype/Scount", fread($fp, 6));
        fclose($fp);
        return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
    }
}
