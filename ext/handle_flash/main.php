<?php
/*
 * Name: Handle Flash
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle Flash files. (No thumbnail is generated for flash files)
 */

class FlashFileHandler extends DataHandlerExtension
{
    protected function create_thumb(string $hash): bool
    {
        copy("ext/handle_flash/thumb.jpg", warehouse_path("thumbs", $hash));
        return true;
    }

    protected function supported_ext(string $ext): bool
    {
        $exts = ["swf"];
        return in_array(strtolower($ext), $exts);
    }

    protected function create_image_from_data(string $filename, array $metadata)
    {
        $image = new Image();

        $image->filesize  = $metadata['size'];
        $image->hash	  = $metadata['hash'];
        $image->filename  = $metadata['filename'];
        $image->ext       = $metadata['extension'];
        $image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
        $image->source    = $metadata['source'];

        $info = getimagesize($filename);
        if (!$info) {
            return null;
        }

        $image->width = $info[0];
        $image->height = $info[1];

        return $image;
    }

    protected function check_contents(string $tmpname): bool
    {
        if (!file_exists($tmpname)) {
            return false;
        }

        $fp = fopen($tmpname, "r");
        $head = fread($fp, 3);
        fclose($fp);
        if (!in_array($head, ["CWS", "FWS"])) {
            return false;
        }

        return true;
    }
}
