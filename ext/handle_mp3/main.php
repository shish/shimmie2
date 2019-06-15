<?php
/*
 * Name: Handle MP3
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle MP3 files
 */

class MP3FileHandler extends DataHandlerExtension
{
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

    protected function create_image_from_data(string $filename, array $metadata)
    {
        $image = new Image();

        //NOTE: No need to set width/height as we don't use it.
        $image->width  = 1;
        $image->height = 1;

        $image->filesize  = $metadata['size'];
        $image->hash      = $metadata['hash'];

        //Filename is renamed to "artist - title.mp3" when the user requests download by using the download attribute & jsmediatags.js
        $image->filename = $metadata['filename'];

        $image->ext       = $metadata['extension'];
        $image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
        $image->source    = $metadata['source'];

        return $image;
    }

    protected function check_contents(string $tmpname): bool
    {
        $success = false;

        if (file_exists($tmpname)) {
            $mimeType = getMimeType($tmpname);

            $success = ($mimeType == 'audio/mpeg');
        }

        return $success;
    }
}
