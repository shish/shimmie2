<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

require_once "config.php";
require_once "events.php";
require_once "media_engine.php";
require_once "video_codecs.php";

/*
* This is used by the media code when there is an error
*/
class MediaException extends SCoreException
{
}

class InsufficientMemoryException extends ServerError
{
}

class Media extends Extension
{
    /** @var MediaTheme */
    protected Themelet $theme;

    private const LOSSLESS_FORMATS = [
        MimeType::WEBP_LOSSLESS,
        MimeType::PNG,
        MimeType::PSD,
        MimeType::BMP,
        MimeType::ICO,
        MimeType::ANI,
        MimeType::GIF
    ];

    private const ALPHA_FORMATS = [
        MimeType::WEBP_LOSSLESS,
        MimeType::WEBP,
        MimeType::PNG,
    ];

    public const RESIZE_TYPE_FIT = "Fit";
    public const RESIZE_TYPE_FIT_BLUR = "Fit Blur";
    public const RESIZE_TYPE_FIT_BLUR_PORTRAIT = "Fit Blur Tall, Fill Wide";
    public const RESIZE_TYPE_FILL =  "Fill";
    public const RESIZE_TYPE_STRETCH =  "Stretch";
    public const DEFAULT_ALPHA_CONVERSION_COLOR = "#00000000";

    public static function imagick_available(): bool
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

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(MediaConfig::FFPROBE_PATH, 'ffprobe');
        $config->set_default_int(MediaConfig::MEM_LIMIT, parse_shorthand_int('8MB'));
        $config->set_default_string(MediaConfig::FFMPEG_PATH, 'ffmpeg');
        $config->set_default_string(MediaConfig::CONVERT_PATH, 'convert');
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("media_rescan/{image_id}", method: "POST", permission: Permissions::RESCAN_MEDIA)) {
            $image = Image::by_id_ex($event->get_iarg('image_id'));

            send_event(new MediaCheckPropertiesEvent($image));
            $image->save_to_db();

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image->id"));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Media Engine Commands");

        //        if (self::imagick_available()) {
        //            try {
        //                $image = new Imagick(realpath('tests/favicon.png'));
        //                $image->clear();
        //                $sb->add_label("ImageMagick detected");
        //            } catch (ImagickException $e) {
        //                $sb->add_label("<b style='color:red'>ImageMagick not detected</b>");
        //            }
        //        } else {
        $sb->start_table();

        $sb->add_text_option(MediaConfig::CONVERT_PATH, "convert", true);
        //        }

        $sb->add_text_option(MediaConfig::FFMPEG_PATH, "ffmpeg", true);
        $sb->add_text_option(MediaConfig::FFPROBE_PATH, "ffprobe", true);

        $sb->add_shorthand_int_option(MediaConfig::MEM_LIMIT, "Mem limit", true);
        $sb->end_table();
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_button("Scan Media Properties", "media_rescan/{$event->image->id}");
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::RESCAN_MEDIA)) {
            $event->add_action("bulk_media_rescan", "Scan Media Properties");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_media_rescan":
                if ($user->can(Permissions::RESCAN_MEDIA)) {
                    $total = 0;
                    $failed = 0;
                    foreach ($event->items as $image) {
                        try {
                            log_debug("media", "Rescanning media for {$image->hash} ({$image->id})");
                            send_event(new MediaCheckPropertiesEvent($image));
                            $image->save_to_db();
                            $total++;
                        } catch (MediaException $e) {
                            $failed++;
                        }
                    }
                    $page->flash("Scanned media properties for $total items, failed for $failed");
                }
                break;
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('media-rescan')
            ->addArgument('id_or_hash', InputArgument::REQUIRED)
            ->setDescription('Refresh metadata for a given post')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $uid = $input->getArgument('id_or_hash');
                $image = Image::by_id_or_hash($uid);
                if ($image) {
                    send_event(new MediaCheckPropertiesEvent($image));
                    $image->save_to_db();
                } else {
                    $output->writeln("No post with ID '$uid'");
                }
                return Command::SUCCESS;
            });
    }

    /**
     * @param MediaResizeEvent $event
     */
    public function onMediaResize(MediaResizeEvent $event): void
    {
        if (!in_array(
            $event->resize_type,
            MediaEngine::RESIZE_TYPE_SUPPORT[$event->engine]
        )) {
            throw new MediaException("Resize type $event->resize_type not supported by selected media engine $event->engine");
        }

        switch ($event->engine) {
            case MediaEngine::GD:
                $info = getimagesize($event->input_path);
                if ($info === false) {
                    throw new MediaException("getimagesize failed for " . $event->input_path);
                }

                self::image_resize_gd(
                    $event->input_path,
                    $info,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_mime,
                    $event->alpha_color,
                    $event->resize_type,
                    $event->target_quality,
                    $event->allow_upscale
                );

                break;
            case MediaEngine::IMAGICK:
                //                if (self::imagick_available()) {
                //                } else {
                self::image_resize_convert(
                    $event->input_path,
                    $event->input_mime,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_mime,
                    $event->alpha_color,
                    $event->resize_type,
                    $event->target_quality,
                    $event->minimize,
                    $event->allow_upscale
                );
                //}
                break;
            case MediaEngine::STATIC:
                copy($event->input_path, $event->output_path);
                break;
            default:
                throw new MediaException("Engine not supported for resize: " . $event->engine);
        }

        // TODO: Get output optimization tools working better
        //        if ($config->get_bool("thumb_optim", false)) {
        //            exec("jpegoptim $outname", $output, $ret);
        //        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^content[=|:]((video)|(audio)|(image)|(unknown))$/i", $event->term, $matches)) {
            $field = $matches[1];
            if ($field === "unknown") {
                $event->add_querylet(new Querylet("video IS NULL OR audio IS NULL OR image IS NULL"));
            } else {
                $event->add_querylet(new Querylet("$field = :true", ["true" => true]));
            }
        } elseif (preg_match("/^ratio([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+):(\d+)$/i", $event->term, $matches)) {
            $cmp = preg_replace('/^:/', '=', $matches[1]);
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width / :width{$event->id} $cmp height / :height{$event->id}", $args));
        } elseif (preg_match("/^size([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)x(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width $cmp :width{$event->id} AND height $cmp :height{$event->id}", $args));
        } elseif (preg_match("/^width([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("width $cmp :width{$event->id}", ["width{$event->id}" => int_escape($matches[2])]));
        } elseif (preg_match("/^height([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("height $cmp :height{$event->id}", ["height{$event->id}" => int_escape($matches[2])]));
        } elseif (preg_match("/^length([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(.+)$/i", $event->term, $matches)) {
            $value = parse_to_milliseconds($matches[2]);
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("length $cmp :length{$event->id}", ["length{$event->id}" => $value]));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Media";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        if ($event->image->width && $event->image->height && $event->image->length) {
            $s = ((int)($event->image->length / 100)) / 10;
            $event->replace('$size', "{$event->image->width}x{$event->image->height}, {$s}s");
        } elseif ($event->image->width && $event->image->height) {
            $event->replace('$size', "{$event->image->width}x{$event->image->height}");
        } elseif ($event->image->length) {
            $s = ((int)($event->image->length / 100)) / 10;
            $event->replace('$size', "{$s}s");
        } else {
            $event->replace('$size', "unknown size");
        }
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
     * https://stackoverflow.com/questions/527532/reasonable-php-memory-limit-for-image-resize
     *
     * @param array{0:int,1:int,2:int,bits?:int,channels?:int} $info The output of getimagesize() for the source file in question.
     * @return int The number of bytes an image resize operation is estimated to use.
     */
    public static function calc_memory_use(array $info): int
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
     * @throws MediaException
     */
    public static function create_thumbnail_ffmpeg(Image $image): bool
    {
        global $config;

        $ffmpeg = $config->get_string(MediaConfig::FFMPEG_PATH);
        if (empty($ffmpeg)) {
            throw new MediaException("ffmpeg command not configured");
        }

        $ok = false;
        $inname = $image->get_image_filename();
        $tmpname = shm_tempnam("ffmpeg_thumb");
        try {
            $outname = $image->get_thumb_filename();

            $orig_size = self::video_size($inname);
            $scaled_size = get_thumbnail_size($orig_size[0], $orig_size[1], true);

            $args = [
                escapeshellarg($ffmpeg),
                "-y", "-i", escapeshellarg($inname),
                "-vf", "scale=$scaled_size[0]:$scaled_size[1],thumbnail",
                "-f", "image2",
                "-vframes", "1",
                "-c:v", "png",
                escapeshellarg($tmpname),
            ];

            $cmd = escapeshellcmd(implode(" ", $args));

            log_debug('media', "Generating thumbnail with command `$cmd`...");

            exec($cmd, $output, $ret);

            if ((int)$ret === (int)0) {
                log_debug('media', "Generating thumbnail with command `$cmd`, returns $ret");
                create_scaled_image($tmpname, $outname, $scaled_size, MimeType::PNG);
                $ok = true;
            } else {
                log_error('media', "Generating thumbnail with command `$cmd`, returns $ret");
            }
        } finally {
            @unlink($tmpname);
        }
        return $ok;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_ffprobe_data(string $filename): array
    {
        global $config;

        $ffprobe = $config->get_string(MediaConfig::FFPROBE_PATH);
        if (empty($ffprobe)) {
            throw new MediaException("ffprobe command not configured");
        }

        $args = [
            escapeshellarg($ffprobe),
            "-print_format", "json",
            "-v", "quiet",
            "-show_format",
            "-show_streams",
            escapeshellarg($filename),
        ];

        $cmd = escapeshellcmd(implode(" ", $args));

        exec($cmd, $output, $ret);

        if ((int)$ret == (int)0) {
            log_debug('media', "Getting media data `$cmd`, returns $ret");
            $output = implode($output);
            return json_decode($output, true);
        } else {
            log_error('media', "Getting media data `$cmd`, returns $ret");
            return [];
        }
    }

    public static function determine_ext(string $mime): string
    {
        $ext = FileExtension::get_for_mime($mime);
        if (empty($ext)) {
            throw new ServerError("Could not determine extension for $mime");
        }
        return $ext;
    }

    //    private static function image_save_imagick(Imagick $image, string $path, string $format, int $output_quality = 80, bool $minimize): void
    //    {
    //        switch ($format) {
    //            case FileExtension::PNG:
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
    //        string $input_path,
    //        string $input_type,
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

    public static function is_lossless(string $filename, string $mime): bool
    {
        if (in_array($mime, self::LOSSLESS_FORMATS)) {
            return true;
        }
        switch ($mime) {
            case MimeType::WEBP:
                return MimeType::is_lossless_webp($filename);
        }
        return false;
    }

    public static function image_resize_convert(
        string $input_path,
        string $input_mime,
        int $new_width,
        int $new_height,
        string $output_filename,
        string $output_mime = null,
        string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        string $resize_type = self::RESIZE_TYPE_FIT,
        int $output_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ): void {
        global $config;

        $convert = $config->get_string(MediaConfig::CONVERT_PATH);

        if (empty($convert)) {
            throw new MediaException("convert command not configured");
        }

        if (empty($output_mime)) {
            $output_mime = $input_mime;
        }

        if ($output_mime == MimeType::WEBP && self::is_lossless($input_path, $input_mime)) {
            $output_mime = MimeType::WEBP_LOSSLESS;
        }

        $bg = "\"$alpha_color\"";
        if (self::supports_alpha($output_mime)) {
            $bg = "none";
        }

        $resize_suffix = "";
        if (!$allow_upscale) {
            $resize_suffix .= "\>";
        }
        if ($resize_type == Media::RESIZE_TYPE_STRETCH) {
            $resize_suffix .= "\!";
        }

        $args = " -auto-orient ";
        $resize_arg = "-resize";
        if ($minimize) {
            $args .= "-strip ";
            $resize_arg = "-thumbnail";
        }

        $input_ext = self::determine_ext($input_mime);

        $file_arg = "{$input_ext}:\"{$input_path}[0]\"";

        if ($resize_type === Media::RESIZE_TYPE_FIT_BLUR_PORTRAIT) {
            if ($new_height > $new_width) {
                $resize_type = Media::RESIZE_TYPE_FIT_BLUR;
            } else {
                $resize_type = Media::RESIZE_TYPE_FILL;
            }
        }

        switch ($resize_type) {
            case Media::RESIZE_TYPE_FIT:
            case Media::RESIZE_TYPE_STRETCH:
                $args .= "{$file_arg} {$resize_arg} {$new_width}x{$new_height}{$resize_suffix} -background {$bg} -flatten ";
                break;
            case Media::RESIZE_TYPE_FILL:
                $args .= "{$file_arg} {$resize_arg} {$new_width}x{$new_height}\^ -background {$bg} -flatten -gravity center -extent {$new_width}x{$new_height} ";
                break;
            case Media::RESIZE_TYPE_FIT_BLUR:
                $blur_size = max(ceil(max($new_width, $new_height) / 25), 5);
                $args .= "{$file_arg} ".
                    "\( -clone 0 -auto-orient -resize {$new_width}x{$new_height}\^ -background {$bg} -flatten -gravity center -fill black -colorize 50% -extent {$new_width}x{$new_height} -blur 0x{$blur_size} \) ".
                    "\( -clone 0 -auto-orient -resize {$new_width}x{$new_height} \) ".
                    "-delete 0 -gravity center -compose over -composite";
                break;
        }


        switch ($output_mime) {
            case MimeType::WEBP_LOSSLESS:
                $args .= ' -define webp:lossless=true';
                break;
            case MimeType::PNG:
                $args .= ' -define png:compression-level=9';
                break;
        }


        $args .= " -quality {$output_quality} ";


        $output_ext = self::determine_ext($output_mime);

        $format = '"%s"  %s   %s:"%s" 2>&1';
        $cmd = sprintf($format, $convert, $args, $output_ext, $output_filename);
        $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
        exec($cmd, $output, $ret);
        if ($ret != 0) {
            throw new MediaException("Resizing image with command `$cmd`, returns $ret, outputting " . implode("\r\n", $output));
        } else {
            log_debug('media', "Generating thumbnail with command `$cmd`, returns $ret");
        }
    }

    /**
     * Performs a resize operation on an image file using GD.
     *
     * @param string $image_filename The source file to be resized.
     * @param array{0:int,1:int,2:int} $info The output of getimagesize() for the source file.
     * @param int $new_width
     * @param int $new_height
     * @param string $output_filename
     * @param ?string $output_mime If set to null, the output file type will be automatically determined via the $info parameter. Otherwise an exception will be thrown.
     * @param int $output_quality Defaults to 80.
     */
    public static function image_resize_gd(
        string $image_filename,
        array $info,
        int $new_width,
        int $new_height,
        string $output_filename,
        ?string $output_mime = null,
        string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        string $resize_type = self::RESIZE_TYPE_FIT,
        int $output_quality = 80,
        bool $allow_upscale = true
    ): void {
        $width = $info[0];
        $height = $info[1];

        if ($output_mime === null) {
            /* If not specified, output to the same format as the original image */
            switch ($info[2]) {
                case IMAGETYPE_GIF:
                    $output_mime = MimeType::GIF;
                    break;
                case IMAGETYPE_JPEG:
                    $output_mime = MimeType::JPEG;
                    break;
                case IMAGETYPE_PNG:
                    $output_mime = MimeType::PNG;
                    break;
                case IMAGETYPE_WEBP:
                    $output_mime = MimeType::WEBP;
                    break;
                case IMAGETYPE_BMP:
                    $output_mime = MimeType::BMP;
                    break;
                default:
                    throw new MediaException("Failed to save the new image - Unsupported MIME type.");
            }
        }

        $memory_use = self::calc_memory_use($info);
        $memory_limit = get_memory_limit();
        if ($memory_use > $memory_limit) {
            throw new InsufficientMemoryException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
        }

        if ($resize_type == Media::RESIZE_TYPE_FIT) {
            list($new_width, $new_height) = get_scaled_by_aspect_ratio($width, $height, $new_width, $new_height);
        }
        if (!$allow_upscale &&
            ($new_width > $width || $new_height > $height)) {
            $new_height = $height;
            $new_width = $width;
        }

        $image = imagecreatefromstring(\Safe\file_get_contents($image_filename));
        if ($image === false) {
            throw new MediaException("Could not load image: " . $image_filename);
        }

        $image_resized = imagecreatetruecolor($new_width, $new_height);
        if ($image_resized === false) {
            throw new MediaException("Could not create output image with dimensions $new_width x $new_height ");
        }

        try {
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
                            throw new MediaException("Unable to allocate transparent color");
                        }

                        // Completely fill the background of the new image with allocated color.
                        if (imagefill($image_resized, 0, 0, $transparency) === false) {
                            throw new MediaException("Unable to fill new image with transparent color");
                        }

                        // Set the background color for new image to transparent
                        imagecolortransparent($image_resized, $transparency);
                    }
                    break;
                case IMAGETYPE_PNG:
                case IMAGETYPE_WEBP:
                    //
                    // More info here:  https://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php
                    //
                    if (imagealphablending($image_resized, false) === false) {
                        throw new MediaException("Unable to disable image alpha blending");
                    }
                    if (imagesavealpha($image_resized, true) === false) {
                        throw new MediaException("Unable to enable image save alpha");
                    }
                    $transparent_color = imagecolorallocatealpha($image_resized, 255, 255, 255, 127);
                    if ($transparent_color === false) {
                        throw new MediaException("Unable to allocate transparent color");
                    }
                    if (imagefilledrectangle($image_resized, 0, 0, $new_width, $new_height, $transparent_color) === false) {
                        throw new MediaException("Unable to fill new image with transparent color");
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
                throw new MediaException("Unable to copy resized image data to new image");
            }

            switch ($output_mime) {
                case MimeType::BMP:
                case MimeType::JPEG:
                    // In case of alpha channels
                    $width = imagesx($image_resized);
                    $height = imagesy($image_resized);
                    $new_image = imagecreatetruecolor($width, $height);
                    if ($new_image === false) {
                        throw new ImageTranscodeException("Could not create image with dimensions $width x $height");
                    }

                    $background_color = Media::hex_color_allocate($new_image, $alpha_color);
                    if (imagefilledrectangle($new_image, 0, 0, $width, $height, $background_color) === false) {
                        throw new ImageTranscodeException("Could not fill background color");
                    }
                    if (imagecopy($new_image, $image_resized, 0, 0, 0, 0, $width, $height) === false) {
                        throw new ImageTranscodeException("Could not copy source image to new image");
                    }

                    imagedestroy($image_resized);
                    $image_resized = $new_image;
                    break;
            }

            switch ($output_mime) {
                case MimeType::BMP:
                    $result = imagebmp($image_resized, $output_filename, true);
                    break;
                case MimeType::WEBP:
                    $result = imagewebp($image_resized, $output_filename, $output_quality);
                    break;
                case MimeType::JPEG:
                    $result = imagejpeg($image_resized, $output_filename, $output_quality);
                    break;
                case MimeType::PNG:
                    $result = imagepng($image_resized, $output_filename, 9);
                    break;
                case MimeType::GIF:
                    $result = imagegif($image_resized, $output_filename);
                    break;
                default:
                    throw new MediaException("Failed to save the new image - Unsupported image type: $output_mime");
            }
            if ($result === false) {
                throw new MediaException("Failed to save the new image, function returned false when saving type: $output_mime");
            }
        } finally {
            @imagedestroy($image);
            @imagedestroy($image_resized);
        }
    }


    public static function supports_alpha(string $mime): bool
    {
        return MimeType::matches_array($mime, self::ALPHA_FORMATS, true);
    }


    /**
     * Determines the dimensions of a video file using ffmpeg.
     *
     * @param string $filename
     * @return array{0: int, 1: int}
     */
    public static function video_size(string $filename): array
    {
        global $config;
        $ffmpeg = $config->get_string(MediaConfig::FFMPEG_PATH);
        $cmd = escapeshellcmd(implode(" ", [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($filename),
            "-vstats"
        ]));
        // \Safe\shell_exec is a little broken
        // https://github.com/thecodingmachine/safe/issues/281
        $output = shell_exec($cmd . " 2>&1");
        if(is_null($output) || $output === false) {
            throw new MediaException("Failed to execute command: $cmd");
        }
        // error_log("Getting size with `$cmd`");

        $regex_sizes = "/Video: .* ([0-9]{1,4})x([0-9]{1,4})/";
        if (preg_match($regex_sizes, $output, $regs)) {
            if (preg_match("/displaymatrix: rotation of (90|270).00 degrees/", $output)) {
                $size = [(int)$regs[2], (int)$regs[1]];
            } else {
                $size = [(int)$regs[1], (int)$regs[2]];
            }
        } else {
            $size = [1, 1];
        }
        log_debug('media', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
        return $size;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $config, $database;
        if ($this->get_version(MediaConfig::VERSION) < 1) {
            $current_value = $config->get_string("thumb_ffmpeg_path");
            if (!empty($current_value)) {
                $config->set_string(MediaConfig::FFMPEG_PATH, $current_value);
            } elseif ($ffmpeg = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffmpeg')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffmpeg, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::FFMPEG_PATH, 'ffmpeg');
                }
            }

            if ($ffprobe = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffprobe')) {
                //ffprobe exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffprobe, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::FFPROBE_PATH, 'ffprobe');
                }
            }

            $current_value = $config->get_string("thumb_convert_path");
            if (!empty($current_value)) {
                $config->set_string(MediaConfig::CONVERT_PATH, $current_value);
            } elseif ($convert = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' convert')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($convert, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::CONVERT_PATH, 'convert');
                }
            }

            $current_value = $config->get_int("thumb_mem_limit");
            if (!empty($current_value)) {
                $config->set_int(MediaConfig::MEM_LIMIT, $current_value);
            }

            $this->set_version(MediaConfig::VERSION, 1);
        }

        if ($this->get_version(MediaConfig::VERSION) < 2) {
            $database->execute("ALTER TABLE images ADD COLUMN image BOOLEAN NULL");

            switch ($database->get_driver_id()) {
                case DatabaseDriverID::PGSQL:
                case DatabaseDriverID::SQLITE:
                    $database->execute('CREATE INDEX images_image_idx ON images(image) WHERE image IS NOT NULL');
                    break;
                default:
                    $database->execute('CREATE INDEX images_image_idx ON images(image)');
                    break;
            }

            $this->set_version(MediaConfig::VERSION, 2);
        }

        if ($this->get_version(MediaConfig::VERSION) < 3) {
            $database->execute("ALTER TABLE images ADD COLUMN video_codec varchar(512) NULL");
            $this->set_version(MediaConfig::VERSION, 3);
        }

        if ($this->get_version(MediaConfig::VERSION) < 4) {
            $database->standardise_boolean("images", "image");
            $this->set_version(MediaConfig::VERSION, 4);
        }

        if ($this->get_version(MediaConfig::VERSION) < 5) {
            $database->execute("UPDATE images SET image = :f WHERE ext IN ('swf','mp3','ani','flv','mp4','m4v','ogv','webm')", ["f" => false]);
            $database->execute("UPDATE images SET image = :t WHERE ext IN ('jpg','jpeg','ico','cur','png')", ["t" => true]);
            $this->set_version(MediaConfig::VERSION, 5);
        }
    }

    public static function hex_color_allocate(mixed $im, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $a = (int)hexdec(substr($hex, 0, 2));
        $b = (int)hexdec(substr($hex, 2, 2));
        $c = (int)hexdec(substr($hex, 4, 2));
        $col = imagecolorallocate($im, $a, $b, $c);
        assert($col !== false);
        return $col;
    }
}
