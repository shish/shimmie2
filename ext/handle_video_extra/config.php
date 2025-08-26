<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraVideoFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_video_extra";
    public ?string $title = "Video File Conversion";

    #[ConfigMeta(
        "Fast only",
        ConfigType::BOOL,
        default: true,
        help: "Fast mode = only container conversions, no re-encoding"
    )]
    public const FAST_ONLY = "handle_video_extra_fast_only";

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = parent::get_config_fields();

        foreach (ExtraVideoFileHandler::INPUT_MIMES as $display => $mime) {
            $mime = new MimeType($mime);
            $fields[ExtraVideoFileHandler::get_mapping_name($mime)] = new ConfigMeta(
                $display,
                ConfigType::STRING,
                options: ExtraVideoFileHandler::OUTPUT_MIMES,
                default: MimeType::WEBM,
            );
        }

        return $fields;
    }
}
