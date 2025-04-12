<?php

declare(strict_types=1);

namespace Shimmie2;

final class CBZFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_cbz";
    public const SUPPORTED_MIME = [MimeType::COMIC_ZIP];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = false;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = false;

        $tmp = $this->get_representative_image($event->image->get_image_filename());
        $info = getimagesize($tmp->str());
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
        $tmp->unlink();
    }

    protected function create_thumb(Image $image): bool
    {
        $cover = $this->get_representative_image($image->get_image_filename());
        ThumbnailUtil::create_scaled_image(
            $cover,
            $image->get_thumb_filename(),
            ThumbnailUtil::get_thumbnail_max_size_scaled(),
            MimeType::get_for_file($cover),
            null
        );
        return true;
    }

    protected function check_contents(Path $tmpname): bool
    {
        $fp = \Safe\fopen($tmpname->str(), "r");
        $head = fread($fp, 4);
        fclose($fp);
        return $head === "PK\x03\x04";
    }

    private function get_representative_image(Path $archive): Path
    {
        $out = shm_tempnam("comic-cover");

        $za = new \ZipArchive();
        $za->open($archive->str());
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
        $out->put_contents(false_throws($za->getFromName($cover)));
        return $out;
    }
}
