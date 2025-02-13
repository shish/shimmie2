<?php

declare(strict_types=1);

namespace Shimmie2;

class RatingsConfig extends ConfigGroup
{
    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_ratings2_version";

    #[ConfigMeta(
        "Default Ratings",
        ConfigType::ARRAY,
        options: "Shimmie2\RatingsConfig::get_ratings_options",
        help: "This controls the default rating search results will be filtered by, and nothing else. To override in your search results, add rating:* to your search.",
    )]
    public const USER_DEFAULTS = "ratings_default";

    /**
     * @return array<string, string>
     */
    public static function get_ratings_options(): array
    {
        global $user;

        $levels = Ratings::get_user_class_privs($user);
        $options = [];
        foreach ($levels as $level) {
            $options[ImageRating::$known_ratings[$level]->name] = $level;
        }
        return $options;
    }

}
