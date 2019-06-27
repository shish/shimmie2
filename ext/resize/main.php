<?php
/*
 * Name: Resize Image
 * Author: jgen <jgen.tech@gmail.com>
 * Description: Allows admins to resize images.
 * License: GPLv2
 * Version: 0.1
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 * Documentation:
 *  This extension allows admins to resize images.
 */

abstract class ResizeConfig
{
    const ENABLED = 'resize_enabled';
    const UPLOAD = 'resize_upload';
    const ENGINE = 'resize_engine';
    const DEFAULT_WIDTH = 'resize_default_width';
    const DEFAULT_HEIGHT = 'resize_default_height';
}

/**
 *	This class handles image resize requests.
 */
class ResizeImage extends Extension
{
    const SUPPORTED_EXT = ["jpg","jpeg","png","gif","webp"];

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
        $config->set_default_bool('resize_enabled', true);
        $config->set_default_bool('resize_upload', false);
        $config->set_default_int('resize_default_width', 0);
        $config->set_default_int('resize_default_height', 0);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user, $config;
        if ($user->is_admin() && $config->get_bool("resize_enabled")
            && in_array($event->image->ext, self::SUPPORTED_EXT)) {
            /* Add a link to resize the image */
            $event->add_part($this->theme->get_resize_html($event->image));
        }
    }
    
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Image Resize");
        $sb->start_table();
        $sb->add_bool_option(ResizeConfig::ENABLED, "Allow resizing images: ", true);
        $sb->add_bool_option(ResizeConfig::UPLOAD, "Resize on upload: ", true);
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
        $event->panel->add_block($sb);
    }
    
    public function onDataUpload(DataUploadEvent $event)
    {
        global $config, $page;

        $image_obj = Image::by_id($event->image_id);

        if ($config->get_bool("resize_upload") == true
                && in_array($event->type, self::SUPPORTED_EXT)) {
            $width = $height = 0;

            if ($config->get_int("resize_default_width") !== 0) {
                $height = $config->get_int("resize_default_width");
            }
            if ($config->get_int("resize_default_height") !== 0) {
                $height = $config->get_int("resize_default_height");
            }
            $isanigif = 0;
            if ($image_obj->ext == "gif") {
                $image_filename = warehouse_path(Image::IMAGE_DIR, $image_obj->hash);
                if (($fh = @fopen($image_filename, 'rb'))) {
                    //check if gif is animated (via http://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                    while (!feof($fh) && $isanigif < 2) {
                        $chunk = fread($fh, 1024 * 100);
                        $isanigif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
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
                send_event(new ThumbnailGenerationEvent($image_obj->hash, $image_obj->ext, true));

                log_info("resize", "Image #{$event->image_id} has been resized to: ".$width."x".$height);
                //TODO: Notify user that image has been resized.
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("resize") && $user->is_admin()) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? int_escape($_POST['image_id']) : null;
            }
            if (empty($image_id)) {
                throw new ImageResizeException("Can not resize Image: No valid Image ID given.");
            }
            
            $image = Image::by_id($image_id);
            if (is_null($image)) {
                $this->theme->display_error(404, "Image not found", "No image in the database has the ID #$image_id");
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
    
    
    // Private functions
    /* ----------------------------- */
    private function resize_image(Image $image_obj, int $width, int $height)
    {
        global $database;
        
        if (($height <= 0) && ($width <= 0)) {
            throw new ImageResizeException("Invalid options for height and width. ($width x $height)");
        }
        
        $hash = $image_obj->hash;
        $image_filename  = warehouse_path(Image::IMAGE_DIR, $hash);

        $info = getimagesize($image_filename);
        if (($image_obj->width != $info[0]) || ($image_obj->height != $info[1])) {
            throw new ImageResizeException("The current image size does not match what is set in the database! - Aborting Resize.");
        }

        list($new_height, $new_width) = $this->calc_new_size($image_obj, $width, $height);

        /* Temp storage while we resize */
        $tmp_filename = tempnam("/tmp", 'shimmie_resize');
        if (empty($tmp_filename)) {
            throw new ImageResizeException("Unable to save temporary image file.");
        }

        image_resize_gd($image_filename, $info, $new_width, $new_height, $tmp_filename);

        $new_image = new Image();
        $new_image->hash = md5_file($tmp_filename);
        $new_image->filesize = filesize($tmp_filename);
        $new_image->filename = 'resized-'.$image_obj->filename;
        $new_image->width = $new_width;
        $new_image->height = $new_height;
        $new_image->ext = $image_obj->ext;

        /* Move the new image into the main storage location */
        $target = warehouse_path(Image::IMAGE_DIR, $new_image->hash);
        if (!@copy($tmp_filename, $target)) {
            throw new ImageResizeException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
        }
        
        /* Remove temporary file */
        @unlink($tmp_filename);

        send_event(new ImageReplaceEvent($image_obj->id, $new_image));
        
        log_info("resize", "Resized Image #{$image_obj->id} - New hash: {$new_image->hash}");
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
