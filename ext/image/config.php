<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageConfig extends ConfigGroup
{
    public ?string $title = "Post Manager";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = 'ext_image_version';

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

    #[ConfigMeta("Show metadata", ConfigType::BOOL)]
    public const SHOW_META = 'image_show_meta';

    #[ConfigMeta("Expires HTTP header (Seconds)", ConfigType::INT, advanced: true)]
    public const EXPIRES = 'image_expires';
}

class ThumbnailConfig extends ConfigGroup
{
    public ?string $title = "Thumbnailing";

    #[ConfigMeta("Tooltip", ConfigType::STRING)]
    public const TIP = 'image_tip';

    #[ConfigMeta("Thumbnail engine", ConfigType::STRING, options: [
        'Built-in GD' => "gd",
        'ImageMagick' => "convert"
    ])]
    public const ENGINE = 'thumb_engine';

    #[ConfigMeta("MIME type", ConfigType::STRING, options: [
        'JPEG' => "image/jpeg",
        'WEBP (Not IE compatible)' => "image/webp"
    ])]
    public const MIME = 'thumb_mime';

    #[ConfigMeta("Max width", ConfigType::INT)]
    public const WIDTH = 'thumb_width';

    #[ConfigMeta("Max height", ConfigType::INT)]
    public const HEIGHT = 'thumb_height';

    #[ConfigMeta("High-DPI Scale %", ConfigType::INT)]
    public const SCALING = 'thumb_scaling';

    #[ConfigMeta("Quality", ConfigType::INT)]
    public const QUALITY = 'thumb_quality';

    #[ConfigMeta("Resize type", ConfigType::STRING, options: "Shimmie2\ThumbnailConfig::get_fit_options")]
    public const FIT = 'thumb_fit';

    #[ConfigMeta("Background color", ConfigType::STRING, ui_type: "color")]
    public const ALPHA_COLOR = 'thumb_alpha_color';

    /**
     * @return array<string, string>
     */
    public static function get_fit_options(): array
    {
        global $config;
        $options = [];
        foreach (MediaEngine::RESIZE_TYPE_SUPPORT[$config->get_string(ThumbnailConfig::ENGINE)] as $type) {
            $options[$type] = $type;
        }
        return $options;
    }
}
