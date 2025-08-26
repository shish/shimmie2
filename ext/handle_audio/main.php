<?php

declare(strict_types=1);

namespace Shimmie2;

final class AudioFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_audio";
    public const SUPPORTED_MIME = [MimeType::MP3, MimeType::OGG_AUDIO, MimeType::FLAC];

    protected function media_check_properties(Image $image): MediaProperties
    {
        try {
            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFPROBE_PATH));
            $command->add_args("-print_format", "json");
            $command->add_args("-v", "quiet");
            $command->add_args("-show_format");
            $command->add_args($image->get_image_filename()->str());
            $output = $command->execute();
            $data = json_decode($output, true);

            $length = (int)floor(floatval($data["format"]["duration"]) * 1000);
            assert($length >= 0);
        } catch (\Exception) {
            $length = null;
        }

        return new MediaProperties(
            width: 0,
            height: 0,
            lossless: $image->get_mime()->base === MimeType::FLAC,
            video: false,
            audio: true,
            image: false,
            video_codec: null,
            length: $length,
        );
    }

    protected function create_thumb(Image $image): bool
    {
        (new Path("ext/handle_audio/thumb.jpg"))->copy($image->get_thumb_filename());
        return true;
    }

    protected function check_contents(Path $tmpname): bool
    {
        $mime = MimeType::get_for_file($tmpname)->base;
        return in_array($mime, self::SUPPORTED_MIME);
    }
}
