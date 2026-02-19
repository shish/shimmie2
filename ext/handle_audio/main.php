<?php

declare(strict_types=1);

namespace Shimmie2;

final class AudioFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_audio";
    public const SUPPORTED_MIME = [MimeType::MP3, MimeType::OGG_AUDIO, MimeType::FLAC];

    protected function media_check_properties(Post $image): MediaProperties
    {
        $width = null;
        $height = null;
        $has_image = false;
        $length = null;

        try {
            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFPROBE_PATH));
            $command->add_args("-print_format", "json");
            $command->add_args("-v", "quiet");
            $command->add_args("-show_format");
            $command->add_args("-show_streams");
            $command->add_args($image->get_media_filename()->str());
            $output = $command->execute();
            $data = json_decode($output, true);

            // Check for embedded artwork (shows up as a video stream)
            if (isset($data["streams"])) {
                foreach ($data["streams"] as $stream) {
                    if ($stream["codec_type"] === "video") {
                        $width = max(1, (int)($stream["width"] ?? 0));
                        $height = max(1, (int)($stream["height"] ?? 0));
                        $has_image = true;
                        break;
                    }
                }
            }

            $length = (int)floor(floatval($data["format"]["duration"]) * 1000);
            assert($length >= 0);
        } catch (\Exception) {
            $length = null;
        }

        return new MediaProperties(
            width: $width,
            height: $height,
            lossless: $image->get_mime()->base === MimeType::FLAC,
            video: false,
            audio: true,
            image: $has_image,
            video_codec: null,
            length: $length,
        );
    }

    protected function create_thumb(Post $image): bool
    {
        $inname = $image->get_media_filename();
        $outname = $image->get_thumb_filename();

        // Try to extract embedded album art using ffmpeg
        $tmpname = shm_tempnam("ffmpeg_audio_thumb", suffix: ".jpg");
        try {
            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFMPEG_PATH));
            $command->add_args("-y", "-hide_banner", "-loglevel", "quiet");
            $command->add_args("-i", $inname->str());
            $command->add_args("-an"); // Disable audio
            $command->add_args($tmpname->str());
            $command->execute();

            // Check if we successfully extracted an image
            if ($tmpname->exists() && filesize($tmpname->str()) > 0) {
                $mime = MimeType::get_for_file($tmpname);
                $scaled_size = ThumbnailUtil::get_thumbnail_size(1, 1, true);
                ThumbnailUtil::create_scaled_image($tmpname, $outname, $scaled_size, $mime);
                $tmpname->unlink();
                return true;
            }
        } catch (\Exception $e) {
            // ffmpeg failed or no embedded artwork found, fall back to default
        } finally {
            if ($tmpname->exists()) {
                $tmpname->unlink();
            }
        }

        // Fall back to default thumb.jpg
        (new Path("ext/handle_audio/thumb.jpg"))->copy($outname);
        return true;
    }

    protected function check_contents(Path $tmpname): bool
    {
        $mime = MimeType::get_for_file($tmpname)->base;
        return in_array($mime, self::SUPPORTED_MIME);
    }
}
