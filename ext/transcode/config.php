<?php

declare(strict_types=1);

namespace Shimmie2;

final class TranscodeImageConfig extends ConfigGroup
{
    public const KEY = "transcode";
    public ?string $title = "Transcode Images";

    #[ConfigMeta("Enable GET args", ConfigType::BOOL, default: false)]
    public const GET_ENABLED = "transcode_get_enabled";

    #[ConfigMeta("Transcode on upload", ConfigType::BOOL, default: false)]
    public const UPLOAD = "transcode_upload";

    #[ConfigMeta("Engine", ConfigType::STRING, default: 'gd', options: ["GD" => "gd", "ImageMagick" => "convert"])]
    public const ENGINE = "transcode_engine";

    #[ConfigMeta("Lossy format quality", ConfigType::INT, default: 80)]
    public const QUALITY = "transcode_quality";

    #[ConfigMeta("Alpha conversion color", ConfigType::STRING, default: "#00000000", input: ConfigInput::COLOR)]
    public const ALPHA_COLOR = "transcode_alpha_color";

    #[ConfigMeta("MIME checks", ConfigType::BOOL, default: false)]
    public const MIME_CHECK_ENABLED = "mime_check_enabled";

    #[ConfigMeta("Allowed MIMEs", ConfigType::ARRAY, default: [], options: "Shimmie2\TranscodeImageConfig::get_mime_options")]
    public const ALLOWED_MIME_STRINGS = "allowed_mime_strings";

    /**
     * @return array<string, MimeType>
     */
    public static function get_mime_options(): array
    {
        $output = [];
        foreach (DataHandlerExtension::get_all_supported_mimes() as $mime) {
            $output[MimeMap::get_name_for_mime($mime)] = $mime;
        }
        return $output;
    }

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = parent::get_config_fields();

        $engine = MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE));
        foreach (TranscodeImage::INPUT_MIMES as $display => $mime) {
            $mime = new MimeType($mime);
            if (MediaEngine::is_input_supported($engine, $mime)) {
                $outputs = [];
                foreach (TranscodeImage::get_supported_output_mimes($engine, $mime) as $name => $output_mime) {
                    $outputs[$name] = (string)$output_mime;
                }
                $fields[TranscodeImage::get_mapping_name($mime)] = new ConfigMeta(
                    $display,
                    ConfigType::STRING,
                    options: $outputs
                );
            }
        }

        return $fields;
    }
}
