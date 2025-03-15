<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML};

final class ImageResizeException extends ServerError
{
}

/**
 *	This class handles image resize requests.
 */
final class ResizeImage extends Extension
{
    public const KEY = "resize";
    /**
     * Needs to be after the data processing extensions
     */
    public function get_priority(): int
    {
        return 55;
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;
        if (
            $user->can(ImagePermission::EDIT_FILES) &&
            $config->get_bool(ResizeConfig::ENABLED) &&
            $this->can_resize_mime($event->image->get_mime())
        ) {
            /* Add a link to resize the image */
            global $config;

            $default_width = $config->get_int(ResizeConfig::DEFAULT_WIDTH, $event->image->width);
            $default_height = $config->get_int(ResizeConfig::DEFAULT_HEIGHT, $event->image->height);

            $event->add_part(SHM_SIMPLE_FORM(
                make_link("resize/{$event->image->id}"),
                rawHTML("
                    <input id='original_width'  name='original_width'  type='hidden' value='{$event->image->width}'>
                    <input id='original_height' name='original_height' type='hidden' value='{$event->image->height}'>
                    <input id='resize_width'  style='width: 70px;' name='resize_width'  type='number' min='1' value='".$default_width."'> x
                    <input id='resize_height' style='width: 70px;' name='resize_height' type='number' min='1' value='".$default_height."'>
                    <br><label><input type='checkbox' id='resize_aspect' name='resize_aspect' style='max-width: 20px;' checked='checked'> Keep Aspect</label>
                    <br><input id='resizebutton' type='submit' value='Resize'>
                ")
            ));
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config, $page;

        if ($config->get_bool(ResizeConfig::UPLOAD) == true
                && $this->can_resize_mime($event->mime)) {
            $image_obj = $event->images[0];

            $width = $config->get_int(ResizeConfig::DEFAULT_WIDTH);
            $height = $config->get_int(ResizeConfig::DEFAULT_HEIGHT);
            $isanigif = 0;
            if ($image_obj->get_mime() == MimeType::GIF) {
                $image_filename = Filesystem::warehouse_path(Image::IMAGE_DIR, $image_obj->hash);
                $fh = \Safe\fopen($image_filename->str(), 'rb');
                //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                while (!feof($fh) && $isanigif < 2) {
                    $chunk = \Safe\fread($fh, 1024 * 100);
                    $isanigif += \Safe\preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
                }
            }
            if ($isanigif == 0) {
                $this->resize_image($image_obj, $width, $height);

                //Need to generate thumbnail again...
                //This only seems to be an issue if one of the sizes was set to 0.
                $image_obj = Image::by_id_ex($image_obj->id); //Must be a better way to grab the new hash than setting this again..
                send_event(new ThumbnailGenerationEvent($image_obj, true));

                Log::info("resize", ">>{$image_obj->id} has been resized to: ".$width."x".$height);
                //TODO: Notify user that image has been resized.
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("resize/{image_id}", method: "POST", permission: ImagePermission::EDIT_FILES)) {
            // Try to get the image ID
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            /* Check if options were given to resize an image. */
            $width = int_escape($event->get_POST('resize_width'));
            $height = int_escape($event->get_POST('resize_height'));
            if ($width || $height) {
                $this->resize_image($image, $width, $height);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/".$image_id));
            }
        }
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        global $config, $user;

        if ($config->get_bool(ResizeConfig::GET_ENABLED) &&
            $user->can(ImagePermission::EDIT_FILES) &&
            $this->can_resize_mime($event->image->get_mime())
        ) {
            $image_width = $event->image->width;
            $image_height = $event->image->height;

            if (isset($event->params['max_height'])) {
                $max_height = int_escape($event->params['max_height']);
            } else {
                $max_height = $image_height;
            }

            if (isset($event->params['max_width'])) {
                $max_width = int_escape($event->params['max_width']);
            } else {
                $max_width = $image_width;
            }

            assert($image_width > 0 && $image_height > 0);
            assert($max_width > 0 && $max_height > 0);

            [$new_width, $new_height] = ThumbnailUtil::get_scaled_by_aspect_ratio($image_width, $image_height, $max_width, $max_height);

            if ($new_width !== $image_width || $new_height !== $image_height) {
                $tmp_filename = shm_tempnam('resize');

                send_event(new MediaResizeEvent(
                    $config->get_string(ResizeConfig::ENGINE),
                    $event->path,
                    $event->mime,
                    $tmp_filename,
                    $new_width,
                    $new_height
                ));

                if ($event->file_modified === true && $event->path !== $event->image->get_image_filename()) {
                    // This means that we're dealing with a temp file that will need cleaned up
                    $event->path->unlink();
                }

                $event->path = $tmp_filename;
                $event->file_modified = true;
            }
        }
    }

    private function can_resize_mime(string $mime): bool
    {
        global $config;
        $engine = $config->get_string(ResizeConfig::ENGINE);
        return MediaEngine::is_input_supported($engine, $mime)
                && MediaEngine::is_output_supported($engine, $mime);
    }


    // Private functions
    /* ----------------------------- */
    private function resize_image(Image $image_obj, int $width, int $height): void
    {
        global $config;

        if (($height <= 0) && ($width <= 0)) {
            throw new ImageResizeException("Invalid options for height and width. ($width x $height)");
        }

        $engine = $config->get_string(ResizeConfig::ENGINE);


        if (!$this->can_resize_mime($image_obj->get_mime())) {
            throw new ImageResizeException("Engine $engine cannot resize selected image");
        }

        $hash = $image_obj->hash;
        $image_filename  = Filesystem::warehouse_path(Image::IMAGE_DIR, $hash);

        $info = \Safe\getimagesize($image_filename->str());
        assert(!is_null($info));
        if (($image_obj->width !== $info[0]) || ($image_obj->height !== $info[1])) {
            throw new ImageResizeException("The current image size does not match what is set in the database! - Aborting Resize.");
        }

        list($new_height, $new_width) = $this->calc_new_size($image_obj, $width, $height);

        /* Temp storage while we resize */
        $tmp_filename = shm_tempnam('resize');

        send_event(new MediaResizeEvent(
            $engine,
            $image_filename,
            $image_obj->get_mime(),
            $tmp_filename,
            $new_width,
            $new_height,
            Media::RESIZE_TYPE_STRETCH
        ));

        send_event(new ImageReplaceEvent($image_obj, $tmp_filename));

        Log::info("resize", "Resized >>{$image_obj->id} - New hash: {$image_obj->hash}");
    }

    /**
     * @return array{0: positive-int, 1: positive-int}
     */
    private function calc_new_size(Image $image_obj, int $width, int $height): array
    {
        /* Calculate the new size of the image */
        if ($height > 0 && $width > 0) {
            $new_height = $height;
            $new_width = $width;
            return [$new_height, $new_width];
        } else {
            $image_width = $image_obj->width;
            $image_height = $image_obj->height;
            assert($image_width > 0 && $image_height > 0);

            if ($width == 0) {
                $factor = $height / $image_height;
            } elseif ($height == 0) {
                $factor = $width / $image_width;
            } else {
                $factor = min($width / $image_width, $height / $image_height);
            }

            $new_width = (int)round($image_width * $factor);
            $new_height = (int)round($image_height * $factor);
            assert($new_width > 0 && $new_height > 0);
            return [$new_height, $new_width];
        }
    }
}
