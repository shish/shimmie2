<?php
/*
 * Name: Handle ICO
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle windows icons
 */

class IcoFileHandler extends DataHandlerExtension
{
    const SUPPORTED_EXTENSIONS = ["ico", "ani", "cur"];


    protected function supported_ext(string $ext): bool
    {
        return in_array(strtolower($ext), self::SUPPORTED_EXTENSIONS);
    }

    protected function create_image_from_data(string $filename, array $metadata)
    {
        $image = new Image();


        $fp = fopen($filename, "r");
        try {
            unpack("Snull/Stype/Scount", fread($fp, 6));
            $subheader = unpack("Cwidth/Cheight/Ccolours/Cnull/Splanes/Sbpp/Lsize/loffset", fread($fp, 16));
        } finally {
            fclose($fp);
        }

        $width = $subheader['width'];
        $height = $subheader['height'];
        $image->width = $width == 0 ? 256 : $width;
        $image->height = $height == 0 ? 256 : $height;

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
        if (!file_exists($file)) {
            return false;
        }
        $fp = fopen($file, "r");
        $header = unpack("Snull/Stype/Scount", fread($fp, 6));
        fclose($fp);
        return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        try {
            create_image_thumb($hash, $type, GraphicsEngine::IMAGICK);
            return true;
        } catch (GraphicsException $e) {
            log_warning("handle_ico", "Could not generate thumbnail. " . $e->getMessage());
            return false;
        }
    }
}
