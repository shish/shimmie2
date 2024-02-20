<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};

// TODO Add warning that rotate doesn't support lossless webp output

/**
 * This class is just a wrapper around SCoreException.
 */
class ImageRotateException extends SCoreException
{
}

/**
 *	This class handles image rotate requests.
 */
class RotateImage extends Extension
{
    public const SUPPORTED_MIME = [MimeType::JPEG, MimeType::PNG, MimeType::GIF, MimeType::WEBP];

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool('rotate_enabled', true);
        $config->set_default_int('rotate_default_deg', 180);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;
        if ($user->can(Permissions::EDIT_FILES) && $config->get_bool("rotate_enabled")
                && MimeType::matches_array($event->image->get_mime(), self::SUPPORTED_MIME)) {
            /* Add a link to rotate the image */
            $event->add_part(SHM_SIMPLE_FORM(
                'rotate/'.$event->image->id,
                INPUT(["type" => 'number', "name" => 'rotate_deg', "id" => "rotate_deg", "placeholder" => "Rotation degrees"]),
                INPUT(["type" => 'submit', "value" => 'Rotate', "id" => "rotatebutton"]),
            ));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Image Rotate");
        $sb->add_bool_option("rotate_enabled", "Allow rotating images: ");
        $sb->add_label("<br>Default Orientation: ");
        $sb->add_int_option("rotate_default_deg");
        $sb->add_label(" deg");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("rotate/{image_id}", method: "POST", permission: Permissions::EDIT_FILES)) {
            // Try to get the image ID
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            /* Check if options were given to rotate an image. */
            $deg = int_escape($event->req_POST('rotate_deg'));

            /* Attempt to rotate the image */
            $this->rotate_image($image_id, $deg);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
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

        $image_filename  = warehouse_path(Image::IMAGE_DIR, $hash);
        if (file_exists($image_filename) === false) {
            throw new ImageRotateException("$image_filename does not exist.");
        }

        $info = \Safe\getimagesize($image_filename);

        // we need to fully-enable phpstan-safe-rules to get the
        // full type hint
        // @phpstan-ignore-next-line
        $memory_use = Media::calc_memory_use($info);
        $memory_limit = get_memory_limit();

        if ($memory_use > $memory_limit) {
            throw new ImageRotateException("The image is too large to rotate given the memory limits. ($memory_use > $memory_limit)");
        }


        /* Attempt to load the image */
        $image = imagecreatefromstring(\Safe\file_get_contents($image_filename));
        if ($image == false) {
            throw new ImageRotateException("Could not load image: ".$image_filename);
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
        if (empty($tmp_filename)) {
            throw new ImageRotateException("Unable to save temporary image file.");
        }

        /* Output to the same format as the original image */
        switch ($info[2]) {
            case IMAGETYPE_GIF:
                $result = imagegif($image_rotated, $tmp_filename);
                break;
            case IMAGETYPE_JPEG:
                $result = imagejpeg($image_rotated, $tmp_filename);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($image_rotated, $tmp_filename, 9);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($image_rotated, $tmp_filename);
                break;
            case IMAGETYPE_BMP:
                $result = imagebmp($image_rotated, $tmp_filename, true);
                break;
            default:
                throw new ImageRotateException("Unsupported image type.");
        }

        if ($result === false) {
            throw new ImageRotateException("Could not save image: ".$tmp_filename);
        }

        $new_hash = \Safe\md5_file($tmp_filename);
        /* Move the new image into the main storage location */
        $target = warehouse_path(Image::IMAGE_DIR, $new_hash);
        if (!@copy($tmp_filename, $target)) {
            throw new ImageRotateException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
        }

        send_event(new ImageReplaceEvent($image_obj, $tmp_filename));

        log_info("rotate", "Rotated >>{$image_id} - New hash: {$new_hash}");
    }
}
