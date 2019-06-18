<?php
/*
 * Name: Graphics
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Provides common functions and settings used for graphic operations.
 * License: MIT
 * Version: 1.0
 */

/*
* This is used by the graphics code when there is an error
*/

use FFMpeg\FFMpeg;

abstract class GraphicsConfig
{
    const FFMPEG_PATH = "graphics_ffmpeg_path";
    const FFPROBE_PATH = "graphics_ffprobe_path";
    const CONVERT_PATH = "graphics_convert_path";
    const VERSION = "ext_graphics_version";
    const MEM_LIMIT = 'graphics_mem_limit';

}

class GraphicsException extends SCoreException
{
}

class GraphicResizeEvent extends Event
{
    public $engine;
    public $input_path;
    public $input_type;
    public $output_path;
    public $target_format;
    public $target_width;
    public $target_height;
    public $target_quality;
    public $minimize;
    public $ignore_aspect_ratio;
    public $allow_upscale;

    public function __construct(String $engine, string $input_path, string $input_type, string $output_path,
                                int $target_width, int $target_height,
                                bool $ignore_aspect_ratio = false,
                                string $target_format = null,
                                int $target_quality = 80,
                                bool $minimize = false,
                                bool $allow_upscale = true)
    {
        assert(in_array($engine, Graphics::GRAPHICS_ENGINES));
        $this->engine = $engine;
        $this->input_path = $input_path;
        $this->input_type = $input_type;
        $this->output_path = $output_path;
        $this->target_height = $target_height;
        $this->target_width = $target_width;
        $this->target_format = $target_format;
        $this->target_quality = $target_quality;
        $this->minimize = $minimize;
        $this->ignore_aspect_ratio = $ignore_aspect_ratio;
        $this->allow_upscale = $allow_upscale;
    }
}

class Graphics extends Extension
{
    const WEBP_LOSSY = "webp-lossy";
    const WEBP_LOSSLESS = "webp-lossless";

    const FFMPEG_ENGINE = "ffmpeg";
    const GD_ENGINE = "gd";
    const IMAGICK_ENGINE = "convert";

    const GRAPHICS_ENGINES = [
        self::GD_ENGINE,
        self::FFMPEG_ENGINE,
        self::IMAGICK_ENGINE
    ];

    const IMAGE_GRAPHICS_ENGINES = [
        "GD" => self::GD_ENGINE,
        "ImageMagick" => self::IMAGICK_ENGINE,
    ];

    const ENGINE_INPUT_SUPPORT = [
        self::GD_ENGINE => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "webp",
        ],
        self::IMAGICK_ENGINE => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "psd",
            "tiff",
            "webp",
            "ico",
        ],
        self::FFMPEG_ENGINE => [
            "avi",
            "mkv",
            "webm",
            "mp4",
            "mov",
            "flv"
        ]
    ];

    const ENGINE_OUTPUT_SUPPORT = [
        self::GD_ENGINE => [
            "gif",
            "jpg",
            "png",
            "webp",
            self::WEBP_LOSSY,
        ],
        self::IMAGICK_ENGINE => [
            "gif",
            "jpg",
            "png",
            "webp",
            self::WEBP_LOSSY,
            self::WEBP_LOSSLESS,
        ],
        self::FFMPEG_ENGINE => [

        ]
    ];

    const LOSSLESS_FORMATS = [
        self::WEBP_LOSSLESS,
        "png",
    ];

    const ALPHA_FORMATS = [
        self::WEBP_LOSSLESS,
        self::WEBP_LOSSY,
        "png",
    ];

    const FORMAT_ALIASES = [
        "tif" => "tiff",
        "jpeg" => "jpg",
    ];


    static function imagick_available(): bool
    {
        return extension_loaded("imagick");
    }

    /**
     * High priority just so that it can be early in the settings
     */
    public function get_priority(): int
    {
        return 30;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(GraphicsConfig::FFPROBE_PATH, 'ffprobe');
        $config->set_default_int(GraphicsConfig::MEM_LIMIT, parse_shorthand_int('8MB'));
        $config->set_default_string(GraphicsConfig::FFMPEG_PATH, '');
        $config->set_default_string(GraphicsConfig::CONVERT_PATH, '');


        if ($config->get_int(GraphicsConfig::VERSION) < 1) {
            $current_value = $config->get_string("thumb_ffmpeg_path");
            if (!empty($current_value)) {
                $config->set_string(GraphicsConfig::FFMPEG_PATH, $current_value);
            } elseif ($ffmpeg = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffmpeg')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffmpeg, PHP_EOL))) {
                    $config->set_default_string(GraphicsConfig::FFMPEG_PATH, 'ffmpeg');
                }
            }

            $current_value = $config->get_string("thumb_convert_path");
            if (!empty($current_value)) {
                $config->set_string(GraphicsConfig::CONVERT_PATH, $current_value);
            } elseif ($convert = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' convert')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($convert, PHP_EOL))) {
                    $config->set_default_string(GraphicsConfig::CONVERT_PATH, 'convert');
                }
            }

            $current_value = $config->get_int("thumb_mem_limit");
            if (!empty($current_value)) {
                $config->set_int(GraphicsConfig::MEM_LIMIT, $current_value);
            }

            $config->set_int(GraphicsConfig::VERSION, 1);
            log_info("graphics", "extension installed");
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Graphics");

//        if (self::imagick_available()) {
//            try {
//                $image = new Imagick(realpath('tests/favicon.png'));
//                $image->clear();
//                $sb->add_label("ImageMagick detected");
//            } catch (ImagickException $e) {
//                $sb->add_label("<b style='color:red'>ImageMagick not detected</b>");
//            }
//        } else {
        $sb->add_text_option(GraphicsConfig::CONVERT_PATH, "convert command: ");
//        }

        $sb->add_text_option(GraphicsConfig::FFMPEG_PATH, "<br/>ffmpeg command: ");

        $sb->add_shorthand_int_option(GraphicsConfig::MEM_LIMIT, "<br />Max memory use: ");

        $event->panel->add_block($sb);

    }

    /**
     * @param GraphicResizeEvent $event
     * @throws GraphicsException
     * @throws InsufficientMemoryException
     */
    public function onGraphicResize(GraphicResizeEvent $event)
    {
        switch ($event->engine) {
            case self::GD_ENGINE:
                $info = getimagesize($event->input_path);
                if ($info === false) {
                    throw new GraphicsException("getimagesize failed for " . $event->input_path);
                }

                self::image_resize_gd(
                    $event->input_path,
                    $info,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_format,
                    $event->ignore_aspect_ratio,
                    $event->target_quality,
                    $event->allow_upscale);

                break;
            case self::IMAGICK_ENGINE:
//                if (self::imagick_available()) {
//                } else {
                self::image_resize_convert(
                    $event->input_path,
                    $event->input_type,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_format,
                    $event->ignore_aspect_ratio,
                    $event->target_quality,
                    $event->minimize,
                    $event->allow_upscale);
                //}
                break;
            default:
                throw new GraphicsException("Engine not supported for resize: " . $event->engine);
        }

        // TODO: Get output optimization tools working better
//        if ($config->get_bool("thumb_optim", false)) {
//            exec("jpegoptim $outname", $output, $ret);
//        }
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
    static function calc_memory_use(array $info): int
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
     * Creates a thumbnail using ffmpeg.
     *
     * @param $hash
     * @return bool true if successful, false if not.
     * @throws GraphicsException
     */
    static function create_thumbnail_ffmpeg($hash): bool
    {
        global $config;

        $ffmpeg = $config->get_string(GraphicsConfig::FFMPEG_PATH);
        if ($ffmpeg == null || $ffmpeg == "") {
            throw new GraphicsException("ffmpeg command configured");
        }

        $inname = warehouse_path(Image::IMAGE_DIR, $hash);
        $outname = warehouse_path(Image::THUMBNAIL_DIR, $hash);

        $orig_size = self::video_size($inname);
        $scaled_size = get_thumbnail_size($orig_size[0], $orig_size[1], true);

        $codec = "mjpeg";
        $quality = $config->get_int(ImageConfig::THUMB_QUALITY);
        if ($config->get_string(ImageConfig::THUMB_TYPE) == "webp") {
            $codec = "libwebp";
        } else {
            // mjpeg quality ranges from 2-31, with 2 being the best quality.
            $quality = floor(31 - (31 * ($quality / 100)));
            if ($quality < 2) {
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
            log_debug('graphics', "Generating thumbnail with command `$cmd`, returns $ret");
            return true;
        } else {
            log_error('graphics', "Generating thumbnail with command `$cmd`, returns $ret");
            return false;
        }
    }

    public static function determine_ext(String $format): String
    {
        $format = self::normalize_format($format);
        switch ($format) {
            case self::WEBP_LOSSLESS:
            case self::WEBP_LOSSY:
                return "webp";
            default:
                return $format;
        }
    }

//    private static function image_save_imagick(Imagick $image, string $path, string $format, int $output_quality = 80, bool $minimize)
//    {
//        switch ($format) {
//            case "png":
//                $result = $image->setOption('png:compression-level', 9);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set png compression option");
//                }
//                break;
//            case Graphics::WEBP_LOSSLESS:
//                $result = $image->setOption('webp:lossless', true);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set lossless webp option");
//                }
//                break;
//            default:
//                $result = $image->setImageCompressionQuality($output_quality);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set compression quality for $path to $output_quality");
//                }
//                break;
//        }
//
//        if (self::supports_alpha($format)) {
//            $result = $image->setImageBackgroundColor(new \ImagickPixel('transparent'));
//        } else {
//            $result = $image->setImageBackgroundColor(new \ImagickPixel('black'));
//        }
//        if ($result !== true) {
//            throw new GraphicsException("Could not set background color");
//        }
//
//
//        if ($minimize) {
//            $profiles = $image->getImageProfiles("icc", true);
//            $result = $image->stripImage();
//            if ($result !== true) {
//                throw new GraphicsException("Could not strip information from image");
//            }
//            if (!empty($profiles)) {
//                $image->profileImage("icc", $profiles['icc']);
//            }
//        }
//
//        $ext = self::determine_ext($format);
//
//        $result = $image->writeImage($ext . ":" . $path);
//        if ($result !== true) {
//            throw new GraphicsException("Could not write image to $path");
//        }
//    }

//    public static function image_resize_imagick(
//        String $input_path,
//        String $input_type,
//        int $new_width,
//        int $new_height,
//        string $output_filename,
//        string $output_type = null,
//        bool $ignore_aspect_ratio = false,
//        int $output_quality = 80,
//        bool $minimize = false,
//        bool $allow_upscale = true
//    ): void
//    {
//        global $config;
//
//        if (!empty($input_type)) {
//            $input_type = self::determine_ext($input_type);
//        }
//
//        try {
//            $image = new Imagick($input_type . ":" . $input_path);
//            try {
//                $result = $image->flattenImages();
//                if ($result !== true) {
//                    throw new GraphicsException("Could not flatten image $input_path");
//                }
//
//                $height = $image->getImageHeight();
//                $width = $image->getImageWidth();
//                if (!$allow_upscale &&
//                    ($new_width > $width || $new_height > $height)) {
//                    $new_height = $height;
//                    $new_width = $width;
//                }
//
//                $result = $image->resizeImage($new_width, $new_width, Imagick::FILTER_LANCZOS, 0, !$ignore_aspect_ratio);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not perform image resize on $input_path");
//                }
//
//
//                if (empty($output_type)) {
//                    $output_type = $input_type;
//                }
//
//                self::image_save_imagick($image, $output_filename, $output_type, $output_quality);
//
//            } finally {
//                $image->destroy();
//            }
//        } catch (ImagickException $e) {
//            throw new GraphicsException("Error while resizing with Imagick: " . $e->getMessage(), $e->getCode(), $e);
//        }
//    }


    public static function image_resize_convert(
        String $input_path,
        String $input_type,
        int $new_width,
        int $new_height,
        string $output_filename,
        string $output_type = null,
        bool $ignore_aspect_ratio = false,
        int $output_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ): void
    {
        global $config;

        $convert = $config->get_string(GraphicsConfig::CONVERT_PATH);

        if ($convert == null || $convert == "") {
            throw new GraphicsException("convert command not configured");
        }

        if (empty($output_type)) {
            $output_type = $input_type;
        }

        $bg = "black";
        if (self::supports_alpha($output_type)) {
            $bg = "none";
        }
        if (!empty($input_type)) {
            $input_type = $input_type . ":";
        }
        $args = "";
        if ($minimize) {
            $args = " -strip -thumbnail";
        }

        $resize_args = "";
        if (!$allow_upscale) {
            $resize_args .= "\>";
        }
        if ($ignore_aspect_ratio) {
            $resize_args .= "\!";
        }

        $format = '"%s" -flatten %s %ux%u%s -quality %u -background %s %s"%s[0]"  %s:"%s" 2>&1';
        $cmd = sprintf($format, $convert, $args, $new_width, $new_height, $resize_args, $output_quality, $bg, $input_type, $input_path, $output_type, $output_filename);
        $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
        exec($cmd, $output, $ret);
        if ($ret != 0) {
            throw new GraphicsException("Resizing image with command `$cmd`, returns $ret, outputting " . implode("\r\n", $output));
        } else {
            log_debug('graphics', "Generating thumbnail with command `$cmd`, returns $ret");
        }
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
     * @throws GraphicsException
     * @throws InsufficientMemoryException if the estimated memory usage exceeds the memory limit.
     */
    public static function image_resize_gd(
        String $image_filename,
        array $info,
        int $new_width,
        int $new_height,
        string $output_filename,
        string $output_type = null,
        bool $ignore_aspect_ratio = false,
        int $output_quality = 80,
        bool $allow_upscale = true
    )
    {
        $width = $info[0];
        $height = $info[1];

        if ($output_type == null) {
            /* If not specified, output to the same format as the original image */
            switch ($info[2]) {
                case IMAGETYPE_GIF:
                    $output_type = "gif";
                    break;
                case IMAGETYPE_JPEG:
                    $output_type = "jpeg";
                    break;
                case IMAGETYPE_PNG:
                    $output_type = "png";
                    break;
                case IMAGETYPE_WEBP:
                    $output_type = "webp";
                    break;
                case IMAGETYPE_BMP:
                    $output_type = "bmp";
                    break;
                default:
                    throw new GraphicsException("Failed to save the new image - Unsupported image type.");
            }
        }

        $memory_use = self::calc_memory_use($info);
        $memory_limit = get_memory_limit();
        if ($memory_use > $memory_limit) {
            throw new InsufficientMemoryException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
        }

        if (!$ignore_aspect_ratio) {
            list($new_width, $new_height) = get_scaled_by_aspect_ratio($width, $height, $new_width, $new_height);
        }
        if (!$allow_upscale &&
            ($new_width > $width || $new_height > $height)) {
            $new_height = $height;
            $new_width = $width;
        }

        $image = imagecreatefromstring(file_get_contents($image_filename));
        $image_resized = imagecreatetruecolor($new_width, $new_height);
        try {
            if ($image === false) {
                throw new GraphicsException("Could not load image: " . $image_filename);
            }
            if ($image_resized === false) {
                throw new GraphicsException("Could not create output image with dimensions $new_width c $new_height ");
            }

            // Handle transparent images
            switch ($info[2]) {
                case IMAGETYPE_GIF:
                    $transparency = imagecolortransparent($image);
                    $pallet_size = imagecolorstotal($image);

                    // If we have a specific transparent color
                    if ($transparency >= 0 && $transparency < $pallet_size) {
                        // Get the original image's transparent color's RGB values
                        $transparent_color = imagecolorsforindex($image, $transparency);

                        // Allocate the same color in the new image resource
                        $transparency = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                        if ($transparency === false) {
                            throw new GraphicsException("Unable to allocate transparent color");
                        }

                        // Completely fill the background of the new image with allocated color.
                        if (imagefill($image_resized, 0, 0, $transparency) === false) {
                            throw new GraphicsException("Unable to fill new image with transparent color");
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
                    if (imagealphablending($image_resized, false) === false) {
                        throw new GraphicsException("Unable to disable image alpha blending");
                    }
                    if (imagesavealpha($image_resized, true) === false) {
                        throw new GraphicsException("Unable to enable image save alpha");
                    }
                    $transparent_color = imagecolorallocatealpha($image_resized, 255, 255, 255, 127);
                    if ($transparent_color === false) {
                        throw new GraphicsException("Unable to allocate transparent color");
                    }
                    if (imagefilledrectangle($image_resized, 0, 0, $new_width, $new_height, $transparent_color) === false) {
                        throw new GraphicsException("Unable to fill new image with transparent color");
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
                ) === false) {
                throw new GraphicsException("Unable to copy resized image data to new image");
            }

            switch ($output_type) {
                case "bmp":
                    $result = imagebmp($image_resized, $output_filename, true);
                    break;
                case "webp":
                case Graphics::WEBP_LOSSY:
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
                    throw new GraphicsException("Failed to save the new image - Unsupported image type: $output_type");
            }
            if ($result === false) {
                throw new GraphicsException("Failed to save the new image, function returned false when saving type: $output_type");
            }
        } finally {
            @imagedestroy($image);
            @imagedestroy($image_resized);
        }
    }

    /**
     * Determines if a file is an animated gif.
     *
     * @param String $image_filename The path of the file to check.
     * @return bool true if the file is an animated gif, false if it is not.
     */
    public static function is_animated_gif(String $image_filename)
    {
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

    public static function supports_alpha(string $format)
    {
        return in_array(self::normalize_format($format), self::ALPHA_FORMATS);
    }

    public static function is_input_supported($engine, $format): bool
    {
        $format = self::normalize_format($format);
        if (!in_array($format, Graphics::ENGINE_INPUT_SUPPORT[$engine])) {
            return false;
        }
        return true;
    }

    public static function is_output_supported($engine, $format): bool
    {
        $format = self::normalize_format($format);
        if (!in_array($format, Graphics::ENGINE_OUTPUT_SUPPORT[$engine])) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a format (normally a file extension) is a variant name of another format (ie, jpg and jpeg).
     * If one is found, then the maine name that the Graphics extension will recognize is returned,
     * otherwise the incoming format is returned.
     *
     * @param $format
     * @return string|null The format name that the graphics extension will recognize.
     */
    static public function normalize_format($format): ?string
    {
        if (array_key_exists($format, Graphics::FORMAT_ALIASES)) {
            return self::FORMAT_ALIASES[$format];
        }
        return $format;
    }


    /**
     * Determines the dimensions of a video file using ffmpeg.
     *
     * @param string $filename
     * @return array [width, height]
     */
    static public function video_size(string $filename): array
    {
        global $config;
        $ffmpeg = $config->get_string(GraphicsConfig::FFMPEG_PATH);
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
        log_debug('graphics', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
        return $size;
    }

}
