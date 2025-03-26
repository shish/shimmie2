<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

final class Media extends Extension
{
    public const KEY = "media";
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

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("media_rescan/{image_id}", method: "POST", permission: MediaPermission::RESCAN_MEDIA)) {
            $image = Image::by_id_ex($event->get_iarg('image_id'));

            send_event(new MediaCheckPropertiesEvent($image));
            $image->save_to_db();

            Ctx::$page->set_redirect(make_link("post/view/$image->id"));
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::DELETE_IMAGE)) {
            $event->add_button("Scan Media Properties", "media_rescan/{$event->image->id}");
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(MediaPermission::RESCAN_MEDIA)) {
            $event->add_action("bulk_media_rescan", "Scan Media Properties");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "bulk_media_rescan":
                if (Ctx::$user->can(MediaPermission::RESCAN_MEDIA)) {
                    $total = 0;
                    $failed = 0;
                    foreach ($event->items as $image) {
                        try {
                            Log::debug("media", "Rescanning media for {$image->hash} ({$image->id})");
                            send_event(new MediaCheckPropertiesEvent($image));
                            $image->save_to_db();
                            $total++;
                        } catch (MediaException $e) {
                            $failed++;
                        }
                    }
                    $event->log_action("Scanned media properties for $total items, failed for $failed");
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
            MediaEngine::RESIZE_TYPE_SUPPORT[$event->engine->value]
        )) {
            throw new MediaException("Resize type $event->resize_type not supported by selected media engine {$event->engine->value}");
        }

        switch ($event->engine) {
            case MediaEngine::GD:
                $info = \Safe\getimagesize($event->input_path->str());
                if ($info === null) {
                    throw new MediaException("Couldn't get dimensions of {$event->input_path->str()}");
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
                $event->input_path->copy($event->output_path);
                break;
            default:
                throw new MediaException("Engine not supported for resize: " . $event->engine->value);
        }

        // TODO: Get output optimization tools working better
        //        if ($config->get_bool("thumb_optim")) {
        //            exec("jpegoptim $outname", $output, $ret);
        //        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^content[=|:]((video)|(audio)|(image)|(unknown))$/i")) {
            $field = $matches[1];
            if ($field === "unknown") {
                $event->add_querylet(new Querylet("video IS NULL OR audio IS NULL OR image IS NULL"));
            } else {
                $event->add_querylet(new Querylet("$field = :true", ["true" => true]));
            }
        } elseif ($matches = $event->matches("/^ratio([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+):(\d+)$/i")) {
            $cmp = \Safe\preg_replace('/^:/', '=', $matches[1]);
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width / :width{$event->id} $cmp height / :height{$event->id}", $args));
        } elseif ($matches = $event->matches("/^size([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)x(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width $cmp :width{$event->id} AND height $cmp :height{$event->id}", $args));
        } elseif ($matches = $event->matches("/^width([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("width $cmp :width{$event->id}", ["width{$event->id}" => int_escape($matches[2])]));
        } elseif ($matches = $event->matches("/^height([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("height $cmp :height{$event->id}", ["height{$event->id}" => int_escape($matches[2])]));
        } elseif ($matches = $event->matches("/^length([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(.+)$/i")) {
            $value = parse_to_milliseconds($matches[2]);
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("length $cmp :length{$event->id}", ["length{$event->id}" => $value]));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Media", $this->theme->get_help_html());
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
        $ffmpeg = Ctx::$config->get_string(MediaConfig::FFMPEG_PATH);
        if (empty($ffmpeg)) {
            throw new MediaException("ffmpeg command not configured");
        }

        $ok = false;
        $inname = $image->get_image_filename();
        $tmpname = shm_tempnam("ffmpeg_thumb");
        try {
            $outname = $image->get_thumb_filename();

            $orig_size = self::video_size($inname);
            $scaled_size = ThumbnailUtil::get_thumbnail_size($orig_size[0], $orig_size[1], true);

            $command = new CommandBuilder($ffmpeg);
            $command->add_flag("-y");
            $command->add_flag("-i");
            $command->add_escaped_arg($inname->str());
            $command->add_flag("-vf");
            $command->add_escaped_arg("scale=$scaled_size[0]:$scaled_size[1],thumbnail");
            $command->add_flag("-f");
            $command->add_escaped_arg("image2");
            $command->add_flag("-vframes");
            $command->add_escaped_arg("1");
            $command->add_flag("-c:v");
            $command->add_escaped_arg("png");
            $command->add_escaped_arg($tmpname->str());
            $command->execute();

            ThumbnailUtil::create_scaled_image($tmpname, $outname, $scaled_size, new MimeType(MimeType::PNG));
            $ok = true;
        } finally {
            @$tmpname->unlink();
        }
        return $ok;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_ffprobe_data(string $filename): array
    {
        $ffprobe = Ctx::$config->get_string(MediaConfig::FFPROBE_PATH);
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

        if ($ret === 0) {
            Log::debug('media', "Getting media data `$cmd`, returns $ret");
            $output = implode($output);
            return json_decode($output, true);
        } else {
            Log::error('media', "Getting media data `$cmd`, returns $ret");
            return [];
        }
    }

    public static function determine_ext(MimeType $mime): string
    {
        $ext = FileExtension::get_for_mime($mime);
        if (empty($ext)) {
            throw new ServerError("Could not determine extension for $mime");
        }
        return $ext;
    }

    public static function is_lossless(Path $filename, MimeType $mime): bool
    {
        if (in_array((string)$mime, self::LOSSLESS_FORMATS)) {
            return true;
        }
        if ($mime->base === MimeType::WEBP) {
            return MimeType::is_lossless_webp($filename);
        }
        return false;
    }

    public static function image_resize_convert(
        Path $input_path,
        MimeType $input_mime,
        int $new_width,
        int $new_height,
        Path $output_filename,
        ?MimeType $output_mime = null,
        string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        string $resize_type = self::RESIZE_TYPE_FIT,
        int $output_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ): void {
        if (empty($output_mime)) {
            $output_mime = $input_mime;
        }

        if ($output_mime->base === MimeType::WEBP && self::is_lossless($input_path, $input_mime)) {
            $output_mime = new MimeType(MimeType::WEBP_LOSSLESS);
        }

        $bg = "\"$alpha_color\"";
        if (self::supports_alpha($output_mime)) {
            $bg = "none";
        }

        $resize_suffix = "";
        if (!$allow_upscale) {
            $resize_suffix .= "\>";
        }
        if ($resize_type === Media::RESIZE_TYPE_STRETCH) {
            $resize_suffix .= "\!";
        }

        $args = " -auto-orient ";
        $resize_arg = "-resize";
        if ($minimize) {
            $args .= "-strip ";
            $resize_arg = "-thumbnail";
        }

        $input_ext = self::determine_ext($input_mime);

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
                $args .= "{$resize_arg} {$new_width}x{$new_height}{$resize_suffix} -background {$bg} -flatten ";
                break;
            case Media::RESIZE_TYPE_FILL:
                $args .= "{$resize_arg} {$new_width}x{$new_height}\^ -background {$bg} -flatten -gravity center -extent {$new_width}x{$new_height} ";
                break;
            case Media::RESIZE_TYPE_FIT_BLUR:
                $blur_size = max(ceil(max($new_width, $new_height) / 25), 5);
                $args .= " ".
                    "\( -clone 0 -auto-orient -resize {$new_width}x{$new_height}\^ -background {$bg} -flatten -gravity center -fill black -colorize 50% -extent {$new_width}x{$new_height} -blur 0x{$blur_size} \) ".
                    "\( -clone 0 -auto-orient -resize {$new_width}x{$new_height} \) ".
                    "-delete 0 -gravity center -compose over -composite";
                break;
        }

        if ($output_mime->base === MimeType::PNG) {
            $args .= ' -define png:compression-level=9';
        } elseif ($output_mime->base == MimeType::WEBP && $output_mime->parameters == MimeType::LOSSLESS_PARAMETER) {
            $args .= ' -define webp:lossless=true -quality 100 ';
        } else {
            $args .= ' -quality '.$output_quality;
        }

        $output_ext = self::determine_ext($output_mime);

        $command = new CommandBuilder(Ctx::$config->req_string(MediaConfig::CONVERT_PATH));
        $command->add_escaped_arg("{$input_ext}:\"{$input_path->str()}[0]\"");
        $command->add_flag($args);
        $command->add_escaped_arg("$output_ext:{$output_filename->str()}");
        $command->execute();
    }

    /**
     * Performs a resize operation on an image file using GD.
     *
     * @param Path $image_filename The source file to be resized.
     * @param array{0:int,1:int,2:int} $info The output of getimagesize() for the source file.
     * @param positive-int $new_width
     * @param positive-int $new_height
     * @param Path $output_filename
     * @param ?MimeType $output_mime If set to null, the output file type will be automatically determined via the $info parameter. Otherwise an exception will be thrown.
     * @param int $output_quality Defaults to 80.
     */
    public static function image_resize_gd(
        Path $image_filename,
        array $info,
        int $new_width,
        int $new_height,
        Path $output_filename,
        ?MimeType $output_mime = null,
        string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        string $resize_type = self::RESIZE_TYPE_FIT,
        int $output_quality = 80,
        bool $allow_upscale = true
    ): void {
        $width = $info[0];
        $height = $info[1];
        assert($width > 0 && $height > 0);

        if ($output_mime === null) {
            /* If not specified, output to the same format as the original image */
            $output_mime = new MimeType(match($info[2]) {
                IMAGETYPE_GIF => MimeType::GIF,
                IMAGETYPE_JPEG => MimeType::JPEG,
                IMAGETYPE_PNG => MimeType::PNG,
                IMAGETYPE_WEBP => MimeType::WEBP,
                IMAGETYPE_BMP => MimeType::BMP,
                default => throw new MediaException("Failed to save the new image - Unsupported MIME type."),
            });
        }

        $memory_use = self::calc_memory_use($info);
        $memory_limit = get_memory_limit();
        if ($memory_use > $memory_limit) {
            throw new InsufficientMemoryException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
        }

        if ($resize_type === Media::RESIZE_TYPE_FIT) {
            list($new_width, $new_height) = ThumbnailUtil::get_scaled_by_aspect_ratio($width, $height, $new_width, $new_height);
        }
        if (!$allow_upscale &&
            ($new_width > $width || $new_height > $height)) {
            $new_height = $height;
            $new_width = $width;
        }

        $image = imagecreatefromstring($image_filename->get_contents());
        if ($image === false) {
            throw new MediaException("Could not load image: " . $image_filename->str());
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

            switch ($output_mime->base) {
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

            $result = match ($output_mime->base) {
                MimeType::BMP => imagebmp($image_resized, $output_filename->str(), true),
                MimeType::WEBP => imagewebp($image_resized, $output_filename->str(), $output_quality),
                MimeType::JPEG => imagejpeg($image_resized, $output_filename->str(), $output_quality),
                MimeType::PNG => imagepng($image_resized, $output_filename->str(), 9),
                MimeType::GIF => imagegif($image_resized, $output_filename->str()),
                default => throw new MediaException("Failed to save the new image - Unsupported image type: $output_mime"),
            };
            if ($result === false) {
                throw new MediaException("Failed to save the new image, function returned false when saving type: $output_mime");
            }
        } finally {
            @imagedestroy($image);
            @imagedestroy($image_resized);
        }
    }


    public static function supports_alpha(MimeType $mime): bool
    {
        return MimeType::matches_array($mime, self::ALPHA_FORMATS, true);
    }


    /**
     * Determines the dimensions of a video file using ffmpeg.
     *
     * @return array{0: positive-int, 1: positive-int}
     */
    public static function video_size(Path $filename): array
    {
        $ffmpeg = Ctx::$config->req_string(MediaConfig::FFMPEG_PATH);
        $cmd = escapeshellcmd(implode(" ", [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($filename->str()),
            "-vstats"
        ]));
        // \Safe\shell_exec is a little broken
        // https://github.com/thecodingmachine/safe/issues/281
        $output = shell_exec($cmd . " 2>&1");
        if (is_null($output) || $output === false) {
            throw new MediaException("Failed to execute command: $cmd");
        }
        // error_log("Getting size with `$cmd`");

        if (\Safe\preg_match("/Video: .* ([0-9]{1,4})x([0-9]{1,4})/", $output, $regs)) {
            $x = (int)$regs[1];
            $y = (int)$regs[2];
            assert($x > 0 && $y > 0);
            if (\Safe\preg_match("/displaymatrix: rotation of (90|270).00 degrees/", $output)) {
                $size = [$y, $x];
            } else {
                $size = [$x, $y];
            }
        } else {
            $size = [1, 1];
        }
        Log::debug('media', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
        return $size;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 1) {
            // The stuff that was here got refactored out of existence
            $this->set_version(1);
        }

        if ($this->get_version() < 2) {
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

            $this->set_version(2);
        }

        if ($this->get_version() < 3) {
            $database->execute("ALTER TABLE images ADD COLUMN video_codec varchar(512) NULL");
            $this->set_version(3);
        }

        if ($this->get_version() < 4) {
            $database->standardise_boolean("images", "image");
            $this->set_version(4);
        }

        if ($this->get_version() < 5) {
            $database->execute("UPDATE images SET image = :f WHERE ext IN ('swf','mp3','ani','flv','mp4','m4v','ogv','webm')", ["f" => false]);
            $database->execute("UPDATE images SET image = :t WHERE ext IN ('jpg','jpeg','ico','cur','png')", ["t" => true]);
            $this->set_version(5);
        }
    }

    public static function hex_color_allocate(mixed $im, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $a = (int)hexdec(substr($hex, 0, 2));
        $b = (int)hexdec(substr($hex, 2, 2));
        $c = (int)hexdec(substr($hex, 4, 2));
        // hexdec(2-digits) will only be int<0, 255>, but phpstan doesn't know that
        // @phpstan-ignore-next-line
        $col = imagecolorallocate($im, $a, $b, $c);
        assert($col !== false);
        return $col;
    }
}
