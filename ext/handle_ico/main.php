<?php

declare(strict_types=1);

namespace Shimmie2;

class IcoFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::ICO, MimeType::ANI, MimeType::WIN_BITMAP, MimeType::ICO_OSX];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = true;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = ($event->image->get_mime() != MimeType::ANI);

        $fp = \Safe\fopen($event->image->get_image_filename(), "r");
        try {
            fseek($fp, 6); // skip header
            $subheader = \Safe\unpack("Cwidth/Cheight/Ccolours/Cnull/Splanes/Sbpp/Lsize/loffset", \Safe\fread($fp, 16));
            $width = $subheader['width'];
            $height = $subheader['height'];
            $event->image->width = $width == 0 ? 256 : $width;
            $event->image->height = $height == 0 ? 256 : $height;
        } finally {
            fclose($fp);
        }
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            create_image_thumb($image, MediaEngine::IMAGICK);
            return true;
        } catch (MediaException $e) {
            log_warning("handle_ico", "Could not generate thumbnail. " . $e->getMessage());
            return false;
        }
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = \Safe\fopen($tmpname, "r");
        $header = \Safe\unpack("Snull/Stype/Scount", \Safe\fread($fp, 6));
        fclose($fp);
        return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
    }
}
