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

    protected function media_check_properties(Image $image): MediaProperties
    {
        $filename = $image->get_image_filename();
        $mime = $image->get_mime();

        $lossless = Media::is_lossless($filename, $mime);
        switch ($mime->base) {
            case MimeType::GIF:
                $video = MimeType::is_animated_gif($filename);
                $length = null; // FIXME
                break;
            case MimeType::WEBP:
                $video = MimeType::is_animated_webp($filename);
                $length = null; // FIXME
                break;
            default:
                $video = false;
                $length = null;
                break;
        }

        $info = getimagesize($image->get_image_filename()->str());
        if ($info) {
            $width = $info[0];
            $height = $info[1];
        } else {
            throw new MediaException("Could not get image size");
        }

        return new MediaProperties(
            width: $width,
            height: $height,
            lossless: $lossless,
            video: $video,
            audio: false,
            image: !$video,
            video_codec: null,
            length: $length,
        );
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
                    OPTION(["value" => "full"], "Full"),
                    OPTION(["value" => "both"], "Fit"),
                    OPTION(["value" => "width"], "Fit Width")
                )
            ), 19);
        }
    }
}
