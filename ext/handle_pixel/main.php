<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{FORM, OPTION, SELECT};

final class PixelFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_pixel";
    public const SUPPORTED_MIME = [
        MimeType::JPEG,
        MimeType::GIF,
        MimeType::PNG,
        MimeType::WEBP,
        MimeType::AVIF,
    ];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $filename = $event->image->get_image_filename();
        $mime = $event->image->get_mime();

        $event->image->lossless = Media::is_lossless($filename, $mime);
        $event->image->audio = false;
        switch ($mime->base) {
            case MimeType::GIF:
                $event->image->video = MimeType::is_animated_gif($filename);
                break;
            case MimeType::WEBP:
                $event->image->video = MimeType::is_animated_webp($filename);
                break;
            default:
                $event->image->video = false;
                break;
        }
        $event->image->image = !$event->image->video;

        $info = getimagesize($event->image->get_image_filename()->str());
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
    }

    protected function check_contents(Path $tmpname): bool
    {
        $valid = [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_WEBP, IMAGETYPE_AVIF];
        $info = getimagesize($tmpname->str());
        return $info && in_array($info[2], $valid);
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            ThumbnailUtil::create_image_thumb($image);
            return true;
        } catch (\Exception $e) {
            throw new UploadException("Error while creating thumbnail: ".$e->getMessage());
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if ($event->context === "view") {
            $event->add_part(FORM(
                SELECT(
                    ["class" => "shm-zoomer"],
                    OPTION(["value" => "full"], "Full Size"),
                    OPTION(["value" => "width"], "Fit Width"),
                    OPTION(["value" => "height"], "Fit Height"),
                    OPTION(["value" => "both"], "Fit Both")
                )
            ), 20);
        }
    }
}
