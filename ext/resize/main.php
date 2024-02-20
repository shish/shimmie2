<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML};

abstract class ResizeConfig
{
    public const ENABLED = 'resize_enabled';
    public const UPLOAD = 'resize_upload';
    public const ENGINE = 'resize_engine';
    public const DEFAULT_WIDTH = 'resize_default_width';
    public const DEFAULT_HEIGHT = 'resize_default_height';
    public const GET_ENABLED = 'resize_get_enabled';
}

class ImageResizeException extends ServerError
{
}

/**
 *	This class handles image resize requests.
 */
class ResizeImage extends Extension
{
    /**
     * Needs to be after the data processing extensions
     */
    public function get_priority(): int
    {
        return 55;
    }


    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool(ResizeConfig::ENABLED, true);
        $config->set_default_bool(ResizeConfig::GET_ENABLED, false);
        $config->set_default_bool(ResizeConfig::UPLOAD, false);
        $config->set_default_string(ResizeConfig::ENGINE, MediaEngine::GD);
        $config->set_default_int(ResizeConfig::DEFAULT_WIDTH, 0);
        $config->set_default_int(ResizeConfig::DEFAULT_HEIGHT, 0);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;
        if (
            $user->can(Permissions::EDIT_FILES) &&
            $config->get_bool(ResizeConfig::ENABLED) &&
            $this->can_resize_mime($event->image->get_mime())
        ) {
            /* Add a link to resize the image */
            global $config;

            $default_width = $config->get_int(ResizeConfig::DEFAULT_WIDTH, $event->image->width);
            $default_height = $config->get_int(ResizeConfig::DEFAULT_HEIGHT, $event->image->height);

            $event->add_part(SHM_SIMPLE_FORM(
                "resize/{$event->image->id}",
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

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Image Resize");
        $sb->start_table();
        $sb->add_choice_option(ResizeConfig::ENGINE, MediaEngine::IMAGE_ENGINES, "Engine", true);
        $sb->add_bool_option(ResizeConfig::ENABLED, "Allow resizing images", true);
        $sb->add_bool_option(ResizeConfig::GET_ENABLED, "Allow GET args", true);
        $sb->add_bool_option(ResizeConfig::UPLOAD, "Resize on upload", true);
        $sb->end_table();
        $sb->start_table();
        $sb->add_table_header("Preset/Default Dimensions");
        $sb->add_label("<tr><th>Width</th><td>");
        $sb->add_int_option(ResizeConfig::DEFAULT_WIDTH);
        $sb->add_label("</td><td>px</td></tr>");
        $sb->add_label("<tr><th>Height</th><td>");
        $sb->add_int_option(ResizeConfig::DEFAULT_HEIGHT);
        $sb->add_label("</td><td>px</td></tr>");
        $sb->add_label("<tr><td></td><td>(enter 0 for no default)</td></tr>");
        $sb->end_table();
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config, $page;

        if ($config->get_bool(ResizeConfig::UPLOAD) == true
                && $this->can_resize_mime($event->mime)) {
            $image_obj = $event->images[0];
            $width = $height = 0;

            if ($config->get_int(ResizeConfig::DEFAULT_WIDTH) !== 0) {
                $height = $config->get_int(ResizeConfig::DEFAULT_WIDTH);
            }
            if ($config->get_int(ResizeConfig::DEFAULT_HEIGHT) !== 0) {
                $height = $config->get_int(ResizeConfig::DEFAULT_HEIGHT);
            }
            $isanigif = 0;
            if ($image_obj->get_mime() == MimeType::GIF) {
                $image_filename = warehouse_path(Image::IMAGE_DIR, $image_obj->hash);
                $fh = \Safe\fopen($image_filename, 'rb');
                //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                while (!feof($fh) && $isanigif < 2) {
                    $chunk = \Safe\fread($fh, 1024 * 100);
                    $isanigif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
                }
            }
            if ($isanigif == 0) {
                $this->resize_image($image_obj, $width, $height);

                //Need to generate thumbnail again...
                //This only seems to be an issue if one of the sizes was set to 0.
                $image_obj = Image::by_id_ex($image_obj->id); //Must be a better way to grab the new hash than setting this again..
                send_event(new ThumbnailGenerationEvent($image_obj, true));

                log_info("resize", ">>{$image_obj->id} has been resized to: ".$width."x".$height);
                //TODO: Notify user that image has been resized.
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("resize/{image_id}", method: "POST", permission: Permissions::EDIT_FILES)) {
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
            $user->can(Permissions::EDIT_FILES) &&
            $this->can_resize_mime($event->image->get_mime())) {
            if (isset($event->params['max_height'])) {
                $max_height = int_escape($event->params['max_height']);
            } else {
                $max_height = $event->image->height;
            }

            if (isset($event->params['max_width'])) {
                $max_width = int_escape($event->params['max_width']);
            } else {
                $max_width = $event->image->width;
            }

            [$new_width, $new_height] = get_scaled_by_aspect_ratio($event->image->width, $event->image->height, $max_width, $max_height);

            if ($new_width !== $event->image->width || $new_height !== $event->image->height) {
                $tmp_filename = shm_tempnam('resize');
                if (empty($tmp_filename)) {
                    throw new ImageResizeException("Unable to save temporary image file.");
                }

                send_event(new MediaResizeEvent(
                    $config->get_string(ResizeConfig::ENGINE),
                    $event->path,
                    $event->mime,
                    $tmp_filename,
                    $new_width,
                    $new_height
                ));

                if ($event->file_modified === true && $event->path != $event->image->get_image_filename()) {
                    // This means that we're dealing with a temp file that will need cleaned up
                    unlink($event->path);
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
        $image_filename  = warehouse_path(Image::IMAGE_DIR, $hash);

        $info = \Safe\getimagesize($image_filename);
        if (($image_obj->width != $info[0]) || ($image_obj->height != $info[1])) {
            throw new ImageResizeException("The current image size does not match what is set in the database! - Aborting Resize.");
        }

        list($new_height, $new_width) = $this->calc_new_size($image_obj, $width, $height);

        /* Temp storage while we resize */
        $tmp_filename = shm_tempnam('resize');
        if (empty($tmp_filename)) {
            throw new ImageResizeException("Unable to save temporary image file.");
        }

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

        log_info("resize", "Resized >>{$image_obj->id} - New hash: {$image_obj->hash}");
    }

    /**
     * @return int[]
     */
    private function calc_new_size(Image $image_obj, int $width, int $height): array
    {
        /* Calculate the new size of the image */
        if ($height > 0 && $width > 0) {
            $new_height = $height;
            $new_width = $width;
            return [$new_height, $new_width];
        } else {
            // Scale the new image
            if ($width == 0) {
                $factor = $height / $image_obj->height;
            } elseif ($height == 0) {
                $factor = $width / $image_obj->width;
            } else {
                $factor = min($width / $image_obj->width, $height / $image_obj->height);
            }

            $new_width = (int)round($image_obj->width * $factor);
            $new_height = (int)round($image_obj->height * $factor);
            return [$new_height, $new_width];
        }
    }
}
