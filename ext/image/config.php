<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageConfig extends ConfigGroup
{
    public const KEY = "image";
    public ?string $title = "Post Manager";

    #[ConfigMeta("Image URL format", ConfigType::STRING, advanced: true)]
    public const ILINK = 'image_ilink';

    #[ConfigMeta("Thumbnail URL format", ConfigType::STRING, advanced: true)]
    public const TLINK = 'image_tlink';

    #[ConfigMeta("Post info", ConfigType::STRING)]
    public const INFO = 'image_info';

    #[ConfigMeta("On delete", ConfigType::STRING, options: [
        "Go to next post" => 'next',
        "Return to post list" => 'list'
    ])]
    public const ON_DELETE = 'image_on_delete';

    #[ConfigMeta("Show metadata", ConfigType::BOOL, default: false)]
    public const SHOW_META = 'image_show_meta';

    #[ConfigMeta("Expires HTTP header (Seconds)", ConfigType::INT, default: 60 * 60 * 24 * 31, advanced: true)]
    public const EXPIRES = 'image_expires';
}

final class ThumbnailConfig extends ConfigGroup
{
    public const KEY = "image";
    public ?string $title = "Thumbnailing";

    #[ConfigMeta("Tooltip", ConfigType::STRING, default: '$tags // $size // $filesize')]
    public const TIP = 'image_tip';

    #[ConfigMeta("Thumbnail engine", ConfigType::STRING, default: 'gd', options: [
        'Built-in GD' => "gd",
        'ImageMagick' => "convert"
    ])]
    public const ENGINE = 'thumb_engine';

    #[ConfigMeta("MIME type", ConfigType::STRING, default: 'image/jpeg', options: [
        'JPEG' => "image/jpeg",
        'WEBP (Not IE compatible)' => "image/webp"
    ])]
    public const MIME = 'thumb_mime';

    #[ConfigMeta("Max width", ConfigType::INT, default: 192)]
    public const WIDTH = 'thumb_width';

    #[ConfigMeta("Max height", ConfigType::INT, default: 192)]
    public const HEIGHT = 'thumb_height';

    #[ConfigMeta("High-DPI Scale %", ConfigType::INT, default: 100, advanced: true)]
    public const SCALING = 'thumb_scaling';

    #[ConfigMeta("Quality", ConfigType::INT, default: 75, advanced: true)]
    public const QUALITY = 'thumb_quality';

    #[ConfigMeta("Resize type", ConfigType::STRING, default: ResizeType::FIT->value, options: "Shimmie2\ThumbnailConfig::get_fit_options")]
    public const FIT = 'thumb_fit';

    #[ConfigMeta("Allow upscaling", ConfigType::BOOL, advanced: true, default: true)]
    public const UPSCALE = 'thumb_upscale';

    #[ConfigMeta("Background color", ConfigType::STRING, default: "#00000000", input: ConfigInput::COLOR)]
    public const ALPHA_COLOR = 'thumb_alpha_color';

    /**
     * @return array<string, string>
     */
    public static function get_fit_options(): array
    {
        $options = [];
        foreach (MediaEngine::RESIZE_TYPE_SUPPORT[Ctx::$config->get(ThumbnailConfig::ENGINE)] as $type) {
            $options[$type->value] = $type->value;
        }
        return $options;
    }
}
