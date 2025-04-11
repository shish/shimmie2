<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsConfig extends ConfigGroup
{
    public const KEY = "rating";

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = parent::get_config_fields();

        $ratings = Ratings::get_sorted_ratings();
        $options = [];
        foreach ($ratings as $key => $rating) {
            $options[$rating->name] = $rating->code;
        }

        foreach (array_keys(UserClass::$known_classes) as $userclass) {
            if ($userclass === "base") {
                continue;
            }
            $key = "ext_rating_{$userclass}_privs";
            $fields[$key] = new ConfigMeta($userclass, ConfigType::ARRAY, options: $options);
        }

        return $fields;
    }
}

final class RatingsUserConfig extends UserConfigGroup
{
    public const KEY = "ratings";

    #[ConfigMeta(
        "Default Ratings",
        ConfigType::ARRAY,
        options: "Shimmie2\RatingsUserConfig::get_ratings_options",
        help: "This controls the default rating search results will be filtered by, and nothing else. To override in your search results, add rating:* to your search.",
    )]
    public const DEFAULTS = "ratings_default";

    /**
     * @return array<string, string>
     */
    public static function get_ratings_options(): array
    {
        $levels = Ratings::get_user_class_privs(Ctx::$user);
        $options = [];
        foreach ($levels as $level) {
            $options[ImageRating::$known_ratings[$level]->name] = $level;
        }
        return $options;
    }
}
