<?php declare(strict_types=1);

abstract class ResizeConfig
{
    const ENABLED = 'resize_enabled';
    const UPLOAD = 'resize_upload';
    const ENGINE = 'resize_engine';
    const DEFAULT_WIDTH = 'resize_default_width';
    const DEFAULT_HEIGHT = 'resize_default_height';
    const GET_ENABLED = 'resize_get_enabled';
}

/**
 *	This class handles image resize requests.
 */
class ResizeImage extends Extension
{
    /** @var ResizeImageTheme */
    protected ?Themelet $theme;

    /**
     * Needs to be after the data processing extensions
     */
    public function get_priority(): int
    {
        return 55;
    }


    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool(ResizeConfig::ENABLED, true);
        $config->set_default_bool(ResizeConfig::GET_ENABLED, false);
        $config->set_default_bool(ResizeConfig::UPLOAD, false);
        $config->set_default_string(ResizeConfig::ENGINE, MediaEngine::GD);
        $config->set_default_int(ResizeConfig::DEFAULT_WIDTH, 0);
        $config->set_default_int(ResizeConfig::DEFAULT_HEIGHT, 0);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user, $config;
        if ($user->can(Permissions::EDIT_FILES) && $config->get_bool(ResizeConfig::ENABLED)
            && $this->can_resize_mime($event->image->get_mime())) {
            /* Add a link to resize the image */
            $event->add_part($this->theme->get_resize_html($event->image));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
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

    public function onDataUpload(DataUploadEvent $event)
    {
        global $config, $page;

        $image_obj = Image::by_id($event->image_id);

        if ($config->get_bool(ResizeConfig::UPLOAD) == true
                && $this->can_resize_mime($event->mime)) {
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
                if (($fh = @fopen($image_filename, 'rb'))) {
                    //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                    while (!feof($fh) && $isanigif < 2) {
                        $chunk = fread($fh, 1024 * 100);
                        $isanigif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
                    }
                }
            }
            if ($isanigif == 0) {
                try {
                    $this->resize_image($image_obj, $width, $height);
                } catch (ImageResizeException $e) {
                    $this->theme->display_resize_error($page, "Error Resizing", $e->error);
                }

                //Need to generate thumbnail again...
                //This only seems to be an issue if one of the sizes was set to 0.
                $image_obj = Image::by_id($event->image_id); //Must be a better way to grab the new hash than setting this again..
                send_event(new ThumbnailGenerationEvent($image_obj->hash, $image_obj->get_mime(), true));

                log_info("resize", ">>{$event->image_id} has been resized to: ".$width."x".$height);
                //TODO: Notify user that image has been resized.
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("resize") && $user->can(Permissions::EDIT_FILES)) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? int_escape($_POST['image_id']) : null;
            }
            if (empty($image_id)) {
                throw new ImageResizeException("Can not resize Image: No valid Post ID given.");
            }

            $image = Image::by_id($image_id);
            if (is_null($image)) {
                $this->theme->display_error(404, "Post not found", "No image in the database has the ID #$image_id");
            } else {

                /* Check if options were given to resize an image. */
                if (isset($_POST['resize_width']) || isset($_POST['resize_height'])) {

                    /* get options */

                    $width = $height = 0;

                    if (isset($_POST['resize_width'])) {
                        $width = int_escape($_POST['resize_width']);
                    }
                    if (isset($_POST['resize_height'])) {
                        $height = int_escape($_POST['resize_height']);
                    }

                    /* Attempt to resize the image */
                    try {
                        $this->resize_image($image, $width, $height);

                        //$this->theme->display_resize_page($page, $image_id);

                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("post/view/".$image_id));
                    } catch (ImageResizeException $e) {
                        $this->theme->display_resize_error($page, "Error Resizing", $e->error);
                    }
                }
            }
        }
    }

    public function onImageDownloading(ImageDownloadingEvent $event)
    {
        global $config, $user;

        if ($config->get_bool(ResizeConfig::GET_ENABLED) &&
            $user->can(Permissions::EDIT_FILES) &&
            $this->can_resize_mime($event->image->get_mime())) {
            if (isset($_GET['max_height'])) {
                $max_height = int_escape($_GET['max_height']);
            } else {
                $max_height = $event->image->height;
            }

            if (isset($_GET['max_width'])) {
                $max_width = int_escape($_GET['max_width']);
            } else {
                $max_width = $event->image->width;
            }

            [$new_width, $new_height] = get_scaled_by_aspect_ratio($event->image->width, $event->image->height, $max_width, $max_height);

            if ($new_width!==$event->image->width || $new_height !==$event->image->height) {
                $tmp_filename = tempnam(sys_get_temp_dir(), 'shimmie_resize');
                if (empty($tmp_filename)) {
                    throw new ImageResizeException("Unable to save temporary image file.");
                }

                $mre = new MediaResizeEvent(
                    $config->get_string(ResizeConfig::ENGINE),
                    $event->path,
                    $event->mime,
                    $tmp_filename,
                    $new_width,
                    $new_height
                );
                send_event($mre);

                if ($event->file_modified===true&&$event->path!=$event->image->get_image_filename()) {
                    // This means that we're dealing with a temp file that will need cleaned up
                    unlink($event->path);
                }

                $event->path = $tmp_filename;
                $event->file_modified = true;
            }
        }
    }

    private function can_resize_mime($mime): bool
    {
        global $config;
        $engine = $config->get_string(ResizeConfig::ENGINE);
        return MediaEngine::is_input_supported($engine, $mime)
                && MediaEngine::is_output_supported($engine, $mime);
    }


    // Private functions
    /* ----------------------------- */
    private function resize_image(Image $image_obj, int $width, int $height)
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

        $info = getimagesize($image_filename);
        if (($image_obj->width != $info[0]) || ($image_obj->height != $info[1])) {
            throw new ImageResizeException("The current image size does not match what is set in the database! - Aborting Resize.");
        }

        list($new_height, $new_width) = $this->calc_new_size($image_obj, $width, $height);

        /* Temp storage while we resize */
        $tmp_filename = tempnam(sys_get_temp_dir(), 'shimmie_resize');
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

        $new_image = new Image();
        $new_image->hash = md5_file($tmp_filename);
        $new_image->filesize = filesize($tmp_filename);
        $new_image->filename = 'resized-'.$image_obj->filename;
        $new_image->width = $new_width;
        $new_image->height = $new_height;

        /* Move the new image into the main storage location */
        $target = warehouse_path(Image::IMAGE_DIR, $new_image->hash);
        if (!@copy($tmp_filename, $target)) {
            throw new ImageResizeException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
        }

        /* Remove temporary file */
        @unlink($tmp_filename);

        send_event(new ImageReplaceEvent($image_obj->id, $new_image));

        log_info("resize", "Resized >>{$image_obj->id} - New hash: {$new_image->hash}");
    }

    /**
     * #return int[]
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

            $new_width = round($image_obj->width * $factor);
            $new_height = round($image_obj->height * $factor);
            return [$new_height, $new_width];
        }
    }
}
