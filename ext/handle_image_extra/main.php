<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraImageFileHandler extends Extension
{
    public const KEY = "handle_image_extra";

    public const INPUT_MIMES = [
        "AVIF" => MimeType::AVIF,
        "BMP" => MimeType::BMP,
        "GIF" => MimeType::GIF,
        "HEIC" => MimeType::HEIC,
        "ICO" => MimeType::ICO,
        "JPG" => MimeType::JPEG,
        "PNG" => MimeType::PNG,
        "PPM" => MimeType::PPM,
        "PSD" => MimeType::PSD,
        "TIFF" => MimeType::TIFF,
        "WEBP" => MimeType::WEBP,
        "TGA" => MimeType::TGA
    ];

    public const OUTPUT_MIMES = [
        // postToSettings converts empty string to null, and Config::get converts
        // null to default, so "Don't convert" needs to be its own distinct value
        "Don't convert" => "-",
        "JPEG" => MimeType::JPEG,
        "PNG" => MimeType::PNG,
        "WEBP (lossy)" => MimeType::WEBP,
        "WEBP (lossless)" => MimeType::WEBP_LOSSLESS,
    ];

    public static function get_mapping_name(MimeType $mime): string
    {
        $mime = MimeMap::get_canonical($mime);
        $flat = preg_replace('/[\.\/]/', '_', $mime->base);
        return "handle_image_extra_conversion_$flat";
    }

    private static function get_mapping(MimeType $mime): ?MimeType
    {
        $val = Ctx::$config->get(self::get_mapping_name($mime));
        assert(is_string($val) || is_null($val));
        return ($val === null || $val === "" || $val === "-") ? null : new MimeType($val);
    }

    #[EventListener]
    public function onBuildSupportedMimes(BuildSupportedMimesEvent $event): void
    {
        $output = [];
        foreach (array_values(self::INPUT_MIMES) as $mime) {
            $mime = new MimeType($mime);
            if (!is_null(self::get_mapping($mime))) {
                $output[] = $mime;
            }
        }
        $event->add_mimes($output);
    }

    #[EventListener(priority: 45)] // Needs to be after upload, but before the processing extensions
    public function onDataUpload(DataUploadEvent $event): void
    {
        $target_mime = self::get_mapping($event->mime);
        if (!empty($target_mime)) {
            $source_name = $event->tmpname;
            $source_mime = $event->mime;

            $command = new CommandBuilder(Ctx::$config->get(MediaConfig::MAGICK_PATH));

            // load file
            $source_type = FileExtension::get_for_mime($source_mime);
            $command->add_args("$source_type:{$source_name->str()}");

            // flatten with optional solid background color
            $command->add_args(
                "-background",
                Media::supports_alpha($target_mime)
                    ? "none"
                    : Ctx::$config->get(ExtraImageFileHandlerConfig::ALPHA_COLOR)
            );
            $command->add_args("-flatten");

            // format-specific compression options
            if ($target_mime->base === MimeType::PNG) {
                $command->add_args("-define", "png:compression-level=9");
            } elseif ($target_mime->base === MimeType::WEBP && ($target_mime->parameters["lossless"] ?? "") === "true") {
                $command->add_args("-define", "webp:lossless=true");
                $command->add_args("-quality", "100");
            } else {
                $command->add_args("-quality", (string)Ctx::$config->get(ExtraImageFileHandlerConfig::QUALITY));
            }

            // write file
            $target_name = shm_tempnam("transcode");
            $ext = FileExtension::get_for_mime($target_mime);
            $command->add_args("$ext:{$target_name->str()}");

            // go
            $command->execute();

            $event->set_tmpname($target_name, $target_mime);
        }
    }
}
