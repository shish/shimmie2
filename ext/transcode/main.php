<?php
/*
 * Name: Transcode Image
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Allows admins to automatically and manually transcode images.
 * License: MIT
 * Version: 1.0
 * Documentation:
 *	Can transcode on-demand and automatically on upload. Config screen allows choosing an output format for each of the supported input formats.
 *  Supports GD and ImageMagick. Both support bmp, gif, jpg, png, and webp as inputs, and jpg, png, and lossy webp as outputs. 
 *  ImageMagick additionally supports tiff and psd inputs, and webp lossless output.
 *  If and image is uanble to be transcoded for any reason, the upload will continue unaffected.
 */

 /*
 * This is used by the image transcoding code when there is an error while transcoding
 */
class ImageTranscodeException extends SCoreException{ }


class TranscodeImage extends Extension
{
    const CONVERSION_ENGINES = [
        "GD" => "gd",
        "ImageMagick" => "convert",
    ];

    const ENGINE_INPUT_SUPPORT = [
        "gd" => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "webp",
        ],
        "convert" => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "psd",
            "tiff",
            "webp",
        ]
    ];

    const ENGINE_OUTPUT_SUPPORT = [
        "gd" => [
            "jpg",
            "png",
            "webp-lossy",
        ],
        "convert" => [
            "jpg",
            "png",
            "webp-lossy",
            "webp-lossless",
        ]
    ];

    const LOSSLESS_FORMATS = [
        "webp-lossless",
        "png",
    ];

    const INPUT_FORMATS = [
        "BMP" => "bmp",
        "GIF" => "gif",
        "JPG" => "jpg",
        "PNG" => "png",
        "PSD" => "psd",
        "TIFF" => "tiff",
        "WEBP" => "webp",
    ];

    const FORMAT_ALIASES = [
        "tif" => "tiff",
        "jpeg" => "jpg",
    ];

    const OUTPUT_FORMATS = [
        "" => "",
        "JPEG (lossy)" => "jpg",
        "PNG (lossless)" => "png",
        "WEBP (lossy)" => "webp-lossy",
        "WEBP (lossless)" => "webp-lossless",
    ];

    /**
     * Need to be after upload, but before the processing extensions
     */
    public function get_priority(): int
    {
        return 45;
    }


    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool('transcode_enabled', true);
        $config->set_default_bool('transcode_upload', false);
        $config->set_default_string('transcode_engine', "gd");
        $config->set_default_int('transcode_quality', 80);

        foreach(array_values(self::INPUT_FORMATS) as $format) {
            $config->set_default_string('transcode_upload_'.$format, "");
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user, $config;

        if ($user->is_admin() && $config->get_bool("resize_enabled")) {
            $engine = $config->get_string("transcode_engine");
            if($this->can_convert_format($engine,$event->image->ext)) {
                $options = $this->get_supported_output_formats($engine, $event->image->ext);
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }
    
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $config;

        $engine = $config->get_string("transcode_engine");


        $sb = new SetupBlock("Image Transcode");
        $sb->add_bool_option("transcode_enabled", "Allow transcoding images: ");
        $sb->add_bool_option("transcode_upload", "<br>Transcode on upload: ");
        $sb->add_choice_option('transcode_engine',self::CONVERSION_ENGINES,"<br />Transcode engine: ");
        foreach(self::INPUT_FORMATS as $display=>$format) {
            if(in_array($format, self::ENGINE_INPUT_SUPPORT[$engine])) {
                $outputs = $this->get_supported_output_formats($engine, $format);
                $sb->add_choice_option('transcode_upload_'.$format,$outputs,"<br />$display to: ");
            }            
        }
        $sb->add_int_option("transcode_quality", "<br/>Lossy format quality: ");
        $event->panel->add_block($sb);
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        global $config, $page;

         if ($config->get_bool("transcode_upload") == true) {
            $ext = strtolower($event->type);

            $ext = $this->clean_format($ext);

            if($event->type=="gif"&&is_animated_gif($event->tmpname)) {
                return;
            }

            if(in_array($ext, array_values(self::INPUT_FORMATS))) {
                $target_format = $config->get_string("transcode_upload_".$ext);
                if(empty($target_format)) {
                    return;
                }
                try {
                    $new_image = $this->transcode_image($event->tmpname, $ext, $target_format);
                    $event->set_type($this->determine_ext($target_format));
                    $event->set_tmpname($new_image);
                } catch(Exception $e) {
                    log_error("transcode","Error while performing upload transcode: ".$e->getMessage());
                    // We don't want to interfere with the upload process, 
                    // so if something goes wrong the untranscoded image jsut continues
                }
            }
        } 
    }



    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("transcode") && $user->is_admin()) {
             $image_id = int_escape($event->get_arg(0));
             if (empty($image_id)) {
                 $image_id = isset($_POST['image_id']) ? int_escape($_POST['image_id']) : null;
             }
             // Try to get the image ID
             if (empty($image_id)) {
                 throw new ImageTranscodeException("Can not resize Image: No valid Image ID given.");
             }
             $image_obj = Image::by_id($image_id);
             if (is_null($image_obj)) {
                 $this->theme->display_error(404, "Image not found", "No image in the database has the ID #$image_id");
             } else {
                 if (isset($_POST['transcode_format'])) {
                    
                     try {
                        $this->transcode_and_replace_image($image_obj, $_POST['transcode_format']);
                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("post/view/".$image_id));
                     } catch (ImageTranscodeException $e) {
                         $this->theme->display_transcode_error($page, "Error Transcoding", $e->getMessage());
                     }
                 }
             }
         }
    }

    
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user, $config;

        $engine = $config->get_string("transcode_engine");

        if ($user->is_admin()) {
            $event->add_action("bulk_transcode","Transcode","",$this->theme->get_transcode_picker_html($this->get_supported_output_formats($engine)));
        }

    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user, $database;

        switch($event->action) {
            case "bulk_transcode":
                if (!isset($_POST['transcode_format'])) {
                    return;
                }
                if ($user->is_admin()) {
                    $format = $_POST['transcode_format'];
                    $total = 0;
                    foreach ($event->items as $id) {
                        try {
                            $database->beginTransaction();
                            $image = Image::by_id($id);
                            if($image==null) {
                                continue;
                            }
            
                            $this->transcode_and_replace_image($image, $format);
                            // If a subsequent transcode fails, the database need to have everything about the previous transcodes recorded already,
                            // otherwise the image entries will be stuck pointing to missing image files
                            $database->commit();
                            $total++;
                        } catch(Exception $e) {
                            log_error("transcode", "Error while bulk transcode on item $id to $format: ".$e->getMessage());
                            try {
                                $database->rollback();
                            } catch (Exception $e) {}
                        }
                    }
                    flash_message("Transcoded  $total items");

                }
                break;
        }
    }

    private function clean_format($format): ?string {
        if(array_key_exists($format, self::FORMAT_ALIASES)) {
            return self::FORMAT_ALIASES[$format];
        }
        return $format;
    }

    private function can_convert_format($engine, $format): bool 
    {
        $format = $this->clean_format($format);
        if(!in_array($format, self::ENGINE_INPUT_SUPPORT[$engine])) {
            return false;
        }
        return true;
    }

    private function get_supported_output_formats($engine, ?String $omit_format = null): array 
    {
        $omit_format = $this->clean_format($omit_format);
        $output = [];
        foreach(self::OUTPUT_FORMATS as $key=>$value) {
            if($value=="") {
                $output[$key] = $value;
                continue;
            }
            if(in_array($value, self::ENGINE_OUTPUT_SUPPORT[$engine]) 
                &&(empty($omit_format)||$omit_format!=$this->determine_ext($value))) {
                $output[$key] = $value;
            }            
        }
        return $output;
    }
    
    private function determine_ext(String $format): String 
    {
        switch($format) {
            case "webp-lossless":
            case "webp-lossy":
                return "webp";
            default:
                return $format;
        }
    }

    private function transcode_and_replace_image(Image $image_obj, String $target_format)
    {
        $target_format = $this->clean_format($target_format);
        $original_file = warehouse_path("images", $image_obj->hash);

        $tmp_filename = $this->transcode_image($original_file, $image_obj->ext, $target_format);
        
        $new_image = new Image();
        $new_image->hash = md5_file($tmp_filename);
        $new_image->filesize = filesize($tmp_filename);
        $new_image->filename = $image_obj->filename;
        $new_image->width = $image_obj->width;
        $new_image->height = $image_obj->height;
        $new_image->ext = $this->determine_ext($target_format);

        /* Move the new image into the main storage location */
        $target = warehouse_path("images", $new_image->hash);
        if (!@copy($tmp_filename, $target)) {
            throw new ImageTranscodeException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
        }
        
        /* Remove temporary file */
        @unlink($tmp_filename);

        send_event(new ImageReplaceEvent($image_obj->id, $new_image));

    }    


    private function transcode_image(String $source_name, String $source_format, string $target_format): string
    {
        global $config;

        if($source_format==$this->determine_ext($target_format)) {
            throw new ImageTranscodeException("Source and target formats are the same: ".$source_format);
        }

        $engine = $config->get_string("transcode_engine");



        if(!$this->can_convert_format($engine,$source_format)) {
            throw new ImageTranscodeException("Engine $engine does not support input format $source_format");
        }
        if(!in_array($target_format, self::ENGINE_OUTPUT_SUPPORT[$engine])) {
            throw new ImageTranscodeException("Engine $engine does not support output format $target_format");
        }

        switch($engine) {
            case "gd":
                return $this->transcode_image_gd($source_name, $source_format, $target_format);
            case "convert":
                return $this->transcode_image_convert($source_name, $source_format, $target_format);
        }

    }

    private function transcode_image_gd(String $source_name, String $source_format, string $target_format): string
    {
        global $config;
            
        $q = $config->get_int("transcode_quality");

        $tmp_name = tempnam("/tmp", "shimmie_transcode");

        $image = imagecreatefromstring(file_get_contents($source_name));
        try {
            $result = false;
            switch($target_format) {
                case "webp-lossy":
                    $result = imagewebp($image, $tmp_name, $q);
                    break;
                case "png":
                    $result = imagepng($image, $tmp_name, 9);
                    break;
                case "jpg":
                    // In case of alpha channels
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $new_image = imagecreatetruecolor($width, $height);
                    if($new_image===false) {
                        throw new ImageTranscodeException("Could not create image with dimensions $width x $height");
                    }
                    try{
                        $black = imagecolorallocate($new_image,  0, 0, 0);
                        if($black===false) {
                            throw new ImageTranscodeException("Could not allocate background color");
                        }
                        if(imagefilledrectangle($new_image, 0, 0, $width, $height, $black)===false) {
                            throw new ImageTranscodeException("Could not fill background color");
                        }
                        if(imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height)===false) {
                            throw new ImageTranscodeException("Could not copy source image to new image");
                        }
                        $result = imagejpeg($new_image, $tmp_name, $q);
                    } finally {
                        imagedestroy($new_image);
                    }
                    break;
            }
            if($result===false) {
                throw new ImageTranscodeException("Error while transcoding ".$source_name." to ".$target_format);
            }
            return $tmp_name;
        } finally {
            imagedestroy($image);
        }
    }

    private function transcode_image_convert(String $source_name, String $source_format, string $target_format): string
    {
        global $config;
            
        $q = $config->get_int("transcode_quality");
        $convert = $config->get_string("thumb_convert_path");

        if($convert==null||$convert=="") 
        {
            throw new ImageTranscodeException("ImageMagick path not configured");
        }
        $ext = $this->determine_ext($target_format);

        $args = "-flatten";
        $bg = "none";
        switch($target_format) {
            case "webp-lossless":
                $args = '-define webp:lossless=true';
                break;
            case "webp-lossy":
                $args = '';
                break;
            case "png":
                $args = '-define png:compression-level=9';
                break;
            default:
                $bg = "white";
                break;
        }
        $tmp_name = tempnam("/tmp", "shimmie_transcode");

        $format = '"%s" %s -quality %u -background %s "%s"  %s:"%s"';
        $cmd = sprintf($format, $convert, $args, $q, $bg, $source_name, $ext, $tmp_name);
        $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
        exec($cmd, $output, $ret);

        log_debug('transcode', "Transcoding with command `$cmd`, returns $ret");

        if($ret!==0) {
            throw new ImageTranscodeException("Transcoding failed with command ".$cmd);
        }

        return $tmp_name;
    }

}
