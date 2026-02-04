<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};

// TODO Add warning that rotate doesn't support lossless webp output

/**
 * This class is just a wrapper around SCoreException.
 */
final class ImageRotateException extends SCoreException
{
}

/**
 *	This class handles image rotate requests.
 */
final class RotateImage extends Extension
{
    public const KEY = "rotate";
    public const SUPPORTED_MIME = [MimeType::JPEG, MimeType::PNG, MimeType::GIF, MimeType::WEBP];

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::EDIT_FILES)
                && MimeType::matches_array($event->image->get_mime(), self::SUPPORTED_MIME)) {
            /* Add a link to rotate the image */
            $event->add_part(SHM_SIMPLE_FORM(
                make_link('rotate/'.$event->image->id),
                INPUT(["type" => 'number', "name" => 'rotate_deg', "id" => "rotate_deg", "placeholder" => "Rotation degrees"]),
                INPUT(["type" => 'submit', "value" => 'Rotate', "id" => "rotatebutton"]),
            ));
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("rotate/{image_id}", method: "POST", permission: ImagePermission::EDIT_FILES)) {
            // Try to get the image ID
            $image_id = $event->get_iarg('image_id');
            Image::by_id_ex($image_id);
            /* Check if options were given to rotate an image. */
            $deg = int_escape($event->POST->req('rotate_deg'));

            /* Attempt to rotate the image */
            $this->rotate_image($image_id, $deg);
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }
    }


    // Private functions
    /* ----------------------------- */
    private function rotate_image(int $image_id, int $deg): void
    {
        if (($deg <= -360) || ($deg >= 360)) {
            throw new ImageRotateException("Invalid options for rotation angle. ($deg)");
        }

        $image_obj = Image::by_id_ex($image_id);
        $hash = $image_obj->hash;

        $image_filename  = Filesystem::warehouse_path(Image::IMAGE_DIR, $hash);
        if (!$image_filename->exists()) {
            throw new ImageRotateException("{$image_filename->str()} does not exist.");
        }

        $info = \Safe\getimagesize($image_filename->str());
        assert(!is_null($info));

        $memory_use = Media::calc_memory_use($info);
        $memory_limit = get_memory_limit();

        if ($memory_use > $memory_limit) {
            throw new ImageRotateException("The image is too large to rotate given the memory limits. ($memory_use > $memory_limit)");
        }


        /* Attempt to load the image */
        $image = imagecreatefromstring(data: $image_filename->get_contents());
        if ($image === false) {
            throw new ImageRotateException("Could not load image: ".$image_filename->str());
        }

        $background_color = 0;
        switch ($info[2]) {
            case IMAGETYPE_PNG:
            case IMAGETYPE_WEBP:
                $background_color = imagecolorallocatealpha($image, 0, 0, 0, 127);
                break;
        }
        if ($background_color === false) {
            throw new ImageRotateException("Unable to allocate transparent color");
        }

        $image_rotated = imagerotate($image, $deg, $background_color);
        if ($image_rotated === false) {
            throw new ImageRotateException("Image rotate failed");
        }

        /* Temp storage while we rotate */
        $tmp_filename = shm_tempnam('rotate');

        /* Output to the same format as the original image */
        $result = match ($info[2]) {
            IMAGETYPE_GIF => imagegif($image_rotated, $tmp_filename->str()),
            IMAGETYPE_JPEG => imagejpeg($image_rotated, $tmp_filename->str()),
            IMAGETYPE_PNG => imagepng($image_rotated, $tmp_filename->str(), 9),
            IMAGETYPE_WEBP => imagewebp($image_rotated, $tmp_filename->str()),
            IMAGETYPE_BMP => imagebmp($image_rotated, $tmp_filename->str(), true),
            default => throw new ImageRotateException("Unsupported image type."),
        };

        if ($result === false) {
            throw new ImageRotateException("Could not save image: ".$tmp_filename->str());
        }

        $new_hash = $tmp_filename->md5();
        /* Move the new image into the main storage location */
        $target = Filesystem::warehouse_path(Image::IMAGE_DIR, $new_hash);
        $tmp_filename->copy($target);
        send_event(new ImageReplaceEvent($image_obj, $tmp_filename));

        Log::info("rotate", "Rotated >>{$image_id} - New hash: {$new_hash}");
    }
}
