<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsBlurConfig extends ConfigGroup
{
    public const KEY = "ratings_blur";

    #[ConfigMeta(
        "Default blurred ratings",
        ConfigType::ARRAY,
        default: ["e"],
        options: "Shimmie2\RatingsBlurConfig::get_global_default_options",
        help: "Unselecting all will revert to default settings, so select '[none]' to blur no images."
    )]
    public const GLOBAL_DEFAULTS = "ext_ratings_blur_defaults";

    /**
     * @return array<string, string>
     */
    public static function get_global_default_options(): array
    {
        $ratings = Ratings::get_sorted_ratings();

        $options = [];
        foreach ($ratings as $key => $rating) {
            $options[$rating->name] = $rating->code;
        }
        $null_option = RatingsBlur::NULL_OPTION;
        $options[$null_option] = $null_option;

        return $options;
    }
}

final class RatingsBlurUserConfig extends UserConfigGroup
{
    public const KEY = "ratings_blur";

    #[ConfigMeta(
        "Blurred ratings",
        ConfigType::ARRAY,
        options: "Shimmie2\RatingsBlurUserConfig::get_user_options",
        help: "Unselecting all will revert to default settings, so select '[none]' to blur no images."
    )]
    public const USER_DEFAULTS = "ratings_blur_default";

    /**
     * @return array<string, string>
     */
    public static function get_user_options(): array
    {
        $levels = Ratings::get_user_class_privs(Ctx::$user);
        $options = [];
        foreach ($levels as $level) {
            $options[ImageRating::$known_ratings[$level]->name] = $level;
        }
        $null_option = RatingsBlur::NULL_OPTION;
        $options[$null_option] = $null_option;
        return $options;
    }

}
