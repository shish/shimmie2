<?php declare(strict_types=1);

class CBZFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::COMIC_ZIP];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = false;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = false;

        $tmp = $this->get_representative_image($event->file_name);
        $info = getimagesize($tmp);
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
        unlink($tmp);
    }

    protected function create_thumb(string $hash, string $mime): bool
    {
        $cover = $this->get_representative_image(warehouse_path(Image::IMAGE_DIR, $hash));
        create_scaled_image(
            $cover,
            warehouse_path(Image::THUMBNAIL_DIR, $hash),
            get_thumbnail_max_size_scaled(),
            MimeType::get_for_file($cover),
            null
        );
        return true;
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = fopen($tmpname, "r");
        $head = fread($fp, 4);
        fclose($fp);
        return $head == "PK\x03\x04";
    }

    private function get_representative_image(string $archive): string
    {
        $out = "data/comic-cover-FIXME.jpg";  // TODO: random

        $za = new ZipArchive();
        $za->open($archive);
        $names = [];
        for ($i=0; $i<$za->numFiles;$i++) {
            $file = $za->statIndex($i);
            $names[] = $file['name'];
        }
        sort($names);
        $cover = $names[0];
        foreach ($names as $name) {
            if (str_contains(strtolower($name), "cover")) {
                $cover = $name;
                break;
            }
        }
        file_put_contents($out, $za->getFromName($cover));
        return $out;
    }
}
