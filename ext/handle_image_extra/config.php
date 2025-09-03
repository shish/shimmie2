<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraImageFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_image_extra";
    public ?string $title = "Image File Conversion";

    #[ConfigMeta("Lossy format quality", ConfigType::INT, default: 80)]
    public const QUALITY = "handle_image_extra_quality";

    #[ConfigMeta("Alpha conversion color", ConfigType::STRING, default: "#00000000", input: ConfigInput::COLOR)]
    public const ALPHA_COLOR = "handle_image_extra_alpha_color";

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = parent::get_config_fields();

        $default_conversions = [
            MimeType::BMP => MimeType::PNG,
            MimeType::HEIC => MimeType::JPEG,
            MimeType::ICO => MimeType::PNG,
            MimeType::PPM => MimeType::PNG,
            MimeType::PSD => MimeType::PNG,
            MimeType::TIFF => MimeType::PNG,
            MimeType::TGA => MimeType::PNG
        ];

        foreach (ExtraImageFileHandler::INPUT_MIMES as $display => $mime) {
            $mime = new MimeType($mime);
            $fields[ExtraImageFileHandler::get_mapping_name($mime)] = new ConfigMeta(
                $display,
                ConfigType::STRING,
                options: ExtraImageFileHandler::OUTPUT_MIMES,
                default: $default_conversions[(string)$mime] ?? null,
            );
        }

        return $fields;
    }
}
