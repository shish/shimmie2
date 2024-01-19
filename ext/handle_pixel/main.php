<?php

declare(strict_types=1);

namespace Shimmie2;

class PixelFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::JPEG, MimeType::GIF, MimeType::PNG, MimeType::WEBP];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $filename = $event->image->get_image_filename();
        $mime = $event->image->get_mime();

        $event->image->lossless = Media::is_lossless($filename, $mime);
        $event->image->audio = false;
        switch ($mime) {
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

        $info = getimagesize($event->image->get_image_filename());
        if ($info) {
            $event->image->width = $info[0];
            $event->image->height = $info[1];
        }
    }

    protected function check_contents(string $tmpname): bool
    {
        $valid = [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
        $info = getimagesize($tmpname);
        return $info && in_array($info[2], $valid);
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            create_image_thumb($image);
            return true;
        } catch (\Exception $e) {
            throw new UploadException("Error while creating thumbnail: ".$e->getMessage());
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if ($event->context == "view") {
            $event->add_part(\MicroHTML\rawHTML("
                <form>
                    <select class='shm-zoomer'>
                        <option value='full'>Full Size</option>
                        <option value='width'>Fit Width</option>
                        <option value='height'>Fit Height</option>
                        <option value='both'>Fit Both</option>
                    </select>
                </form>
            "), 20);
        }
    }
}
