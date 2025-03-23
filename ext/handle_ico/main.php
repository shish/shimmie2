<?php

declare(strict_types=1);

namespace Shimmie2;

final class IcoFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_ico";
    public const SUPPORTED_MIME = [MimeType::ICO, MimeType::ANI, MimeType::WIN_BITMAP, MimeType::ICO_OSX];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = true;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = ($event->image->get_mime()->base !== MimeType::ANI);

        $fp = \Safe\fopen($event->image->get_image_filename()->str(), "r");
        try {
            fseek($fp, 6); // skip header
            $subheader = \Safe\unpack("Cwidth/Cheight/Ccolours/Cnull/Splanes/Sbpp/Lsize/loffset", \Safe\fread($fp, 16));
            $width = $subheader['width'];
            $height = $subheader['height'];
            $event->image->width = $width === 0 ? 256 : $width;
            $event->image->height = $height === 0 ? 256 : $height;
        } finally {
            fclose($fp);
        }
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            $engine = defined("UNITTEST") ? MediaEngine::STATIC : MediaEngine::IMAGICK;
            ThumbnailUtil::create_image_thumb($image, $engine);
            return true;
        } catch (MediaException $e) {
            Log::warning("handle_ico", "Could not generate thumbnail. " . $e->getMessage());
            return false;
        }
    }

    protected function check_contents(Path $tmpname): bool
    {
        $fp = \Safe\fopen($tmpname->str(), "r");
        $header = \Safe\unpack("Snull/Stype/Scount", \Safe\fread($fp, 6));
        fclose($fp);
        return ($header['null'] === 0 && ($header['type'] === 0 || $header['type'] === 1));
    }
}
