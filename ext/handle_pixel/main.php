<?php declare(strict_types=1);

class PixelFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::JPEG, MimeType::GIF, MimeType::PNG, MimeType::WEBP];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = Media::is_lossless($event->file_name, $event->mime);
        $event->image->audio = false;
        switch ($event->mime) {
            case MimeType::GIF:
                $event->image->video = MimeType::is_animated_gif($event->file_name);
                break;
            case MimeType::WEBP:
                $event->image->video = MimeType::is_animated_webp($event->file_name);
                break;
            default:
                $event->image->video = false;
                break;
        }
        $event->image->image = !$event->image->video;

        $info = getimagesize($event->file_name);
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

    protected function create_thumb(string $hash, string $mime): bool
    {
        try {
            create_image_thumb($hash, $mime);
            return true;
        } catch (InsufficientMemoryException $e) {
            $tsize = get_thumbnail_max_size_scaled();
            $thumb = imagecreatetruecolor($tsize[0], min($tsize[1], 64));
            $white = imagecolorallocate($thumb, 255, 255, 255);
            $black = imagecolorallocate($thumb, 0, 0, 0);
            imagefill($thumb, 0, 0, $white);
            log_warning("handle_pixel", "Insufficient memory while creating thumbnail: ".$e->getMessage());
            imagestring($thumb, 5, 10, 24, "Image Too Large :(", $black);
            return true;
        } catch (Exception $e) {
            log_error("handle_pixel", "Error while creating thumbnail: ".$e->getMessage());
            return false;
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        $event->add_part("
			<form>
				<select class='shm-zoomer'>
					<option value='full'>Full Size</option>
					<option value='width'>Fit Width</option>
					<option value='height'>Fit Height</option>
					<option value='both'>Fit Both</option>
				</select>
			</form>
		", 20);
    }
}
