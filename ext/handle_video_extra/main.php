<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraVideoFileHandler extends Extension
{
    public const KEY = "handle_video_extra";

    public const INPUT_MIMES = [
        "ASF" => MimeType::ASF,
        "AVI" => MimeType::AVI,
        "MOV" => MimeType::QUICKTIME,
        "WMV" => MimeType::WMV,
        "FLV" => MimeType::FLASH_VIDEO,
        "MPEG" => MimeType::MPEG,
        "MKV" => MimeType::MKV,
        "OGV" => MimeType::OGG_VIDEO,
    ];

    public const OUTPUT_MIMES = [
        "Don't convert" => "",
        "WEBM" => MimeType::WEBM,
        "MP4" => MimeType::MP4_VIDEO,
    ];

    public static function get_mapping_name(MimeType $mime): string
    {
        $mime = MimeMap::get_canonical($mime);
        $flat = preg_replace('/[\.\/]/', '_', $mime->base);
        return "handle_video_extra_conversion_$flat";
    }

    private static function get_mapping(MimeType $mime): ?MimeType
    {
        $val = Ctx::$config->get(self::get_mapping_name($mime));
        assert(is_string($val) || is_null($val));
        return ($val === null || $val === "") ? null : new MimeType($val);
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
            $target_name = shm_tempnam("handle_video_extra");

            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFMPEG_PATH));
            $command->add_args("-y", "-hide_banner", "-loglevel", "quiet");
            $command->add_args("-i", $source_name->str());
            if (Ctx::$config->get(ExtraVideoFileHandlerConfig::FAST_ONLY)) {
                $command->add_args("-c", "copy");
            }
            $command->add_args("-f", FileExtension::get_for_mime($target_mime));
            $command->add_args($target_name->str());

            try {
                $command->execute();
            } catch (CommandException $e) {
                if (str_contains($e->output, "Only VP8")) {
                    throw new UserError("Only VP8/VP9/AV1 video, Vorbis/Opus audio, and WebVTT subtitles are supported.");
                } else {
                    throw $e;
                }
            }

            $event->set_tmpname($target_name, $target_mime);
        }
    }
}
