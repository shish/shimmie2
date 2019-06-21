<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc functions                                                            *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Move a file from PHP's temporary area into shimmie's image storage
 * hierarchy, or throw an exception trying.
 *
 * @param DataUploadEvent $event
 * @throws UploadException
 */
function move_upload_to_archive(DataUploadEvent $event): void
{
    $target = warehouse_path(Image::IMAGE_DIR, $event->hash);
    if (!@copy($event->tmpname, $target)) {
        $errors = error_get_last();
        throw new UploadException(
            "Failed to copy file from uploads ({$event->tmpname}) to archive ($target): ".
            "{$errors['type']} / {$errors['message']}"
        );
    }
}

/**
 * Add a directory full of images
 *
 * @param string $base
 * @return array
 */
function add_dir(string $base): array
{
    $results = [];

    foreach (list_files($base) as $full_path) {
        $short_path = str_replace($base, "", $full_path);
        $filename = basename($full_path);

        $tags = path_to_tags($short_path);
        $result = "$short_path (".str_replace(" ", ", ", $tags).")... ";
        try {
            add_image($full_path, $filename, $tags);
            $result .= "ok";
        } catch (UploadException $ex) {
            $result .= "failed: ".$ex->getMessage();
        }
        $results[] = $result;
    }

    return $results;
}

/**
 * Sends a DataUploadEvent for a file.
 *
 * @param string $tmpname
 * @param string $filename
 * @param string $tags
 * @throws UploadException
 */
function add_image(string $tmpname, string $filename, string $tags): void
{
    assert(file_exists($tmpname));

    $pathinfo = pathinfo($filename);
    $metadata = [];
    $metadata['filename'] = $pathinfo['basename'];
    if (array_key_exists('extension', $pathinfo)) {
        $metadata['extension'] = $pathinfo['extension'];
    }
    
    $metadata['tags'] = Tag::explode($tags);
    $metadata['source'] = null;
    $event = new DataUploadEvent($tmpname, $metadata);
    send_event($event);
}

/**
 * Gets an the extension defined in MIME_TYPE_MAP for a file.
 *
 * @param String $file_path
 * @return String The extension that was found.
 * @throws UploadException if the mimetype could not be determined, or if an extension for hte mimetype could not be found.
 */
function get_extension_from_mime(String $file_path): String
{
    $mime = mime_content_type($file_path);
    if (!empty($mime)) {
        $ext = get_extension($mime);
        if (!empty($ext)) {
            return $ext;
        }
        throw new UploadException("Could not determine extension for mimetype ".$mime);
    }
    throw new UploadException("Could not determine file mime type: ".$file_path);
}


/**
 * Given a full size pair of dimensions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact.
 * Optionally uses the High-DPI scaling setting to adjust the final resolution.
 *
 * @param int $orig_width
 * @param int $orig_height
 * @param bool $use_dpi_scaling Enables the High-DPI scaling.
 * @return array
 */
function get_thumbnail_size(int $orig_width, int $orig_height, bool $use_dpi_scaling = false): array
{
    global $config;

    if ($orig_width === 0) {
        $orig_width = 192;
    }
    if ($orig_height === 0) {
        $orig_height = 192;
    }

    if ($orig_width > $orig_height * 5) {
        $orig_width = $orig_height * 5;
    }
    if ($orig_height > $orig_width * 5) {
        $orig_height = $orig_width * 5;
    }


    if($use_dpi_scaling) {
        $max_size = get_thumbnail_max_size_scaled();
        $max_width  = $max_size[0];
        $max_height = $max_size[1];
    } else {
        $max_width = $config->get_int('thumb_width');
        $max_height = $config->get_int('thumb_height');
    }

    $xscale = ($max_height / $orig_height);
    $yscale = ($max_width / $orig_width);
    $scale = ($xscale < $yscale) ? $xscale : $yscale;

    if ($scale > 1 && $config->get_bool('thumb_upscale')) {
        return [(int)$orig_width, (int)$orig_height];
    } else {
        return [(int)($orig_width*$scale), (int)($orig_height*$scale)];
    }
}

/**
 * Fetches the thumbnails height and width settings and applies the High-DPI scaling setting before returning the dimensions.
 *
 * @return array [width, height]
 */
function get_thumbnail_max_size_scaled(): array
{
    global $config;

    $scaling = $config->get_int("thumb_scaling");
    $max_width  = $config->get_int('thumb_width') * ($scaling/100);
    $max_height = $config->get_int('thumb_height') * ($scaling/100);
    return [$max_width, $max_height];
}

/**
 * Creates a thumbnail file using ImageMagick's convert command.
 *
 * @param $hash
 * @param string $input_type Optional, allows specifying the input format. Usually not necessary.
 * @return bool true is successful, false if not.
 */
function create_thumbnail_convert($hash, $input_type = ""): bool
{
    global $config;

    $inname  = warehouse_path(Image::IMAGE_DIR, $hash);
    $outname = warehouse_path(Image::THUMBNAIL_DIR, $hash);

    $q = $config->get_int("thumb_quality");
    $convert = $config->get_string("thumb_convert_path");

    if ($convert==null||$convert=="") {
        return false;
    }

    //  ffff imagemagick fails sometimes, not sure why
    //$format = "'%s' '%s[0]' -format '%%[fx:w] %%[fx:h]' info:";
    //$cmd = sprintf($format, $convert, $inname);
    //$size = shell_exec($cmd);
    //$size = explode(" ", trim($size));
    list($w, $h) = get_thumbnail_max_size_scaled();


    // running the call with cmd.exe requires quoting for our paths
    $type = $config->get_string('thumb_type');

    $options = "";
    if (!$config->get_bool('thumb_upscale')) {
        $options .= "\>";
    }

    $bg = "black";
    if ($type=="webp") {
        $bg = "none";
    }
    if(!empty($input_type)) {
        $input_type = $input_type.":";
    }
    $format = '"%s" -flatten -strip -thumbnail %ux%u%s -quality %u -background %s %s"%s[0]"  %s:"%s" 2>&1';
    $cmd = sprintf($format, $convert, $w, $h, $options, $q, $bg,$input_type, $inname, $type, $outname);
    $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
    exec($cmd, $output, $ret);
    if ($ret!=0) {
        log_warning('imageboard/misc', "Generating thumbnail with command `$cmd`, returns $ret, outputting ".implode("\r\n",$output));
    } else {
        log_debug('imageboard/misc', "Generating thumbnail with command `$cmd`, returns $ret");
    }

    if ($config->get_bool("thumb_optim", false)) {
        exec("jpegoptim $outname", $output, $ret);
    }

    return true;
}

/**
 * Creates a thumbnail using ffmpeg.
 *
 * @param $hash
 * @return bool true if successful, false if not.
 */
function create_thumbnail_ffmpeg($hash): bool
{
    global $config;

    $ffmpeg = $config->get_string("thumb_ffmpeg_path");
    if ($ffmpeg==null||$ffmpeg=="") {
        return false;
    }

    $inname  = warehouse_path(Image::IMAGE_DIR, $hash);
    $outname = warehouse_path(Image::THUMBNAIL_DIR, $hash);

    $orig_size = video_size($inname);
    $scaled_size = get_thumbnail_size($orig_size[0], $orig_size[1], true);
    
    $codec = "mjpeg";
    $quality = $config->get_int("thumb_quality");
    if ($config->get_string("thumb_type")=="webp") {
        $codec = "libwebp";
    } else {
        // mjpeg quality ranges from 2-31, with 2 being the best quality.
        $quality = floor(31 - (31 * ($quality/100)));
        if ($quality<2) {
            $quality = 2;
        }
    }

    $args = [
        escapeshellarg($ffmpeg),
        "-y", "-i", escapeshellarg($inname),
        "-vf", "thumbnail,scale={$scaled_size[0]}:{$scaled_size[1]}",
        "-f", "image2",
        "-vframes", "1",
        "-c:v", $codec,
        "-q:v", $quality,
        escapeshellarg($outname),
    ];

    $cmd = escapeshellcmd(implode(" ", $args));

    exec($cmd, $output, $ret);

    if ((int)$ret == (int)0) {
        log_debug('imageboard/misc', "Generating thumbnail with command `$cmd`, returns $ret");
        return true;
    } else {
        log_error('imageboard/misc', "Generating thumbnail with command `$cmd`, returns $ret");
        return false;
    }
}

/**
 * Determines the dimensions of a video file using ffmpeg.
 *
 * @param string $filename
 * @return array [width, height]
 */
function video_size(string $filename): array
{
    global $config;
    $ffmpeg = $config->get_string("thumb_ffmpeg_path");
    $cmd = escapeshellcmd(implode(" ", [
        escapeshellarg($ffmpeg),
        "-y", "-i", escapeshellarg($filename),
        "-vstats"
    ]));
    $output = shell_exec($cmd . " 2>&1");
    // error_log("Getting size with `$cmd`");

    $regex_sizes = "/Video: .* ([0-9]{1,4})x([0-9]{1,4})/";
    if (preg_match($regex_sizes, $output, $regs)) {
        if (preg_match("/displaymatrix: rotation of (90|270).00 degrees/", $output)) {
            $size = [$regs[2], $regs[1]];
        } else {
            $size = [$regs[1], $regs[2]];
        }
    } else {
        $size = [1, 1];
    }
    log_debug('imageboard/misc', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
    return $size;
}

/**
 * Check Memory usage limits
 *
 * Old check:   $memory_use = (filesize($image_filename)*2) + ($width*$height*4) + (4*1024*1024);
 * New check:   $memory_use = $width * $height * ($bits_per_channel) * channels * 2.5
 *
 * It didn't make sense to compute the memory usage based on the NEW size for the image. ($width*$height*4)
 * We need to consider the size that we are GOING TO instead.
 *
 * The factor of 2.5 is simply a rough guideline.
 * http://stackoverflow.com/questions/527532/reasonable-php-memory-limit-for-image-resize
 *
 * @param array $info The output of getimagesize() for the source file in question.
 * @return int The number of bytes an image resize operation is estimated to use.
 */
function calc_memory_use(array $info): int
{
    if (isset($info['bits']) && isset($info['channels'])) {
        $memory_use = ($info[0] * $info[1] * ($info['bits'] / 8) * $info['channels'] * 2.5) / 1024;
    } else {
        // If we don't have bits and channel info from the image then assume default values
        // of 8 bits per color and 4 channels (R,G,B,A) -- ie: regular 24-bit color
        $memory_use = ($info[0] * $info[1] * 1 * 4 * 2.5) / 1024;
    }
    return (int)$memory_use;
}

/**
 * Performs a resize operation on an image file using GD.
 *
 * @param String $image_filename The source file to be resized.
 * @param array $info The output of getimagesize() for the source file.
 * @param int $new_width
 * @param int $new_height
 * @param string $output_filename
 * @param string|null $output_type If set to null, the output file type will be automatically determined via the $info parameter. Otherwise an exception will be thrown.
 * @param int $output_quality Defaults to 80.
 * @throws ImageResizeException
 * @throws InsufficientMemoryException if the estimated memory usage exceeds the memory limit.
 */
function image_resize_gd(
    String $image_filename,
    array $info,
    int $new_width,
    int $new_height,
    string $output_filename,
    string $output_type=null,
    int $output_quality = 80
) {
    $width = $info[0];
    $height = $info[1];

    if ($output_type==null) {
        /* If not specified, output to the same format as the original image */
        switch ($info[2]) {
            case IMAGETYPE_GIF:   $output_type = "gif";    break;
            case IMAGETYPE_JPEG:  $output_type = "jpeg";   break;
            case IMAGETYPE_PNG:   $output_type = "png";    break;
            case IMAGETYPE_WEBP:  $output_type = "webp";   break;
            case IMAGETYPE_BMP:   $output_type = "bmp";    break;
            default: throw new ImageResizeException("Failed to save the new image - Unsupported image type.");
        }
    }

    $memory_use = calc_memory_use($info);
    $memory_limit = get_memory_limit();
    if ($memory_use > $memory_limit) {
        throw new InsufficientMemoryException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
    }

    $image = imagecreatefromstring(file_get_contents($image_filename));
    $image_resized = imagecreatetruecolor($new_width, $new_height);
    try {
        if ($image===false) {
            throw new ImageResizeException("Could not load image: ".$image_filename);
        }
        if ($image_resized===false) {
            throw new ImageResizeException("Could not create output image with dimensions $new_width c $new_height ");
        }

        // Handle transparent images
        switch ($info[2]) {
            case IMAGETYPE_GIF:
                $transparency = imagecolortransparent($image);
                $palletsize = imagecolorstotal($image);

                // If we have a specific transparent color
                if ($transparency >= 0 && $transparency < $palletsize) {
                    // Get the original image's transparent color's RGB values
                    $transparent_color = imagecolorsforindex($image, $transparency);

                    // Allocate the same color in the new image resource
                    $transparency = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                    if ($transparency===false) {
                        throw new ImageResizeException("Unable to allocate transparent color");
                    }
                    
                    // Completely fill the background of the new image with allocated color.
                    if (imagefill($image_resized, 0, 0, $transparency)===false) {
                        throw new ImageResizeException("Unable to fill new image with transparent color");
                    }

                    // Set the background color for new image to transparent
                    imagecolortransparent($image_resized, $transparency);
                }
                break;
            case IMAGETYPE_PNG:
            case IMAGETYPE_WEBP:
                //
                // More info here:  http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php
                //
                if (imagealphablending($image_resized, false)===false) {
                    throw new ImageResizeException("Unable to disable image alpha blending");
                }
                if (imagesavealpha($image_resized, true)===false) {
                    throw new ImageResizeException("Unable to enable image save alpha");
                }
                $transparent_color = imagecolorallocatealpha($image_resized, 255, 255, 255, 127);
                if ($transparent_color===false) {
                    throw new ImageResizeException("Unable to allocate transparent color");
                }
                if (imagefilledrectangle($image_resized, 0, 0, $new_width, $new_height, $transparent_color)===false) {
                    throw new ImageResizeException("Unable to fill new image with transparent color");
                }
                break;
        }
        
        // Actually resize the image.
        if (imagecopyresampled(
            $image_resized,
            $image,
            0,
            0,
            0,
            0,
            $new_width,
            $new_height,
            $width,
            $height
            )===false) {
            throw new ImageResizeException("Unable to copy resized image data to new image");
        }


        switch ($output_type) {
            case "bmp":
                $result = imagebmp($image_resized, $output_filename, true);
                break;
            case "webp":
                $result = imagewebp($image_resized, $output_filename, $output_quality);
                break;
            case "jpg":
            case "jpeg":
                $result = imagejpeg($image_resized, $output_filename, $output_quality);
                break;
            case "png":
                $result = imagepng($image_resized, $output_filename, 9);
                break;
            case "gif":
                $result = imagegif($image_resized, $output_filename);
                break;
            default:
                throw new ImageResizeException("Failed to save the new image - Unsupported image type: $output_type");
        }
        if ($result==false) {
            throw new ImageResizeException("Failed to save the new image, function returned false when saving type: $output_type");
        }
    } finally {
        imagedestroy($image);
        imagedestroy($image_resized);
    }
}

/**
 * Determines if a file is an animated gif.
 *
 * @param String $image_filename The path of the file to check.
 * @return bool true if the file is an animated gif, false if it is not.
 */
function is_animated_gif(String $image_filename) {
    $is_anim_gif = 0;
    if (($fh = @fopen($image_filename, 'rb'))) {
        //check if gif is animated (via http://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
        while (!feof($fh) && $is_anim_gif < 2) {
            $chunk = fread($fh, 1024 * 100);
            $is_anim_gif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }
    }
    return ($is_anim_gif == 0);
}