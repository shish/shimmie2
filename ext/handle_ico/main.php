<?php declare(strict_types=1);

class IcoFileHandler extends DataHandlerExtension
{
    const SUPPORTED_EXTENSIONS = ["ico", "ani", "cur"];

    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        if (in_array($event->ext, self::SUPPORTED_EXTENSIONS)) {
            $event->image->lossless = true;
            $event->image->video = false;
            $event->image->audio = false;
            $event->image->image = ($event->ext!="ani");

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
    }

    protected function supported_ext(string $ext): bool
    {
        return in_array(strtolower($ext), self::SUPPORTED_EXTENSIONS);
    }

    protected function create_image_from_data(string $filename, array $metadata)
    {
        $image = new Image();

        $image->filesize = $metadata['size'];
        $image->hash = $metadata['hash'];
        $image->filename = $metadata['filename'];
        $image->ext = $metadata['extension'];
        $image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
        $image->source = $metadata['source'];

        return $image;
    }

    protected function check_contents(string $file): bool
    {
        $fp = fopen($file, "r");
        $header = unpack("Snull/Stype/Scount", fread($fp, 6));
        fclose($fp);
        return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        try {
            create_image_thumb($hash, $type, MediaEngine::IMAGICK);
            return true;
        } catch (MediaException $e) {
            log_warning("handle_ico", "Could not generate thumbnail. " . $e->getMessage());
            return false;
        }
    }
}
