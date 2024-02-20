<?php

declare(strict_types=1);

namespace Shimmie2;

class CBZFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::COMIC_ZIP];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = false;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = false;

        $tmp = $this->get_representative_image($event->image->get_image_filename());
        $info = getimagesize($tmp);
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
        unlink($tmp);
    }

    protected function create_thumb(Image $image): bool
    {
        $cover = $this->get_representative_image($image->get_image_filename());
        create_scaled_image(
            $cover,
            $image->get_thumb_filename(),
            get_thumbnail_max_size_scaled(),
            MimeType::get_for_file($cover),
            null
        );
        return true;
    }

    protected function check_contents(string $tmpname): bool
    {
        $fp = \Safe\fopen($tmpname, "r");
        $head = fread($fp, 4);
        fclose($fp);
        return $head == "PK\x03\x04";
    }

    private function get_representative_image(string $archive): string
    {
        $out = "data/comic-cover-FIXME.jpg";  // TODO: random

        $za = new \ZipArchive();
        $za->open($archive);
        $names = [];
        for ($i = 0; $i < $za->numFiles;$i++) {
            $file = false_throws($za->statIndex($i));
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
