<?php declare(strict_types=1);

class FlashFileHandler extends DataHandlerExtension
{
    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        switch ($event->ext) {
            case "swf":
                $event->image->lossless = true;
                $event->image->video = true;

                $info = getimagesize($event->file_name);
                if (!$info) {
                    return null;
                }
                $event->image->image = false;

                $event->image->width = $info[0];
                $event->image->height = $info[1];

                break;
        }
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        if (!Media::create_thumbnail_ffmpeg($hash)) {
            copy("ext/handle_flash/thumb.jpg", warehouse_path(Image::THUMBNAIL_DIR, $hash));
        }
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



        return $image;
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = fopen($tmpname, "r");
        $head = fread($fp, 3);
        fclose($fp);
        return in_array($head, ["CWS", "FWS"]);
    }
}
