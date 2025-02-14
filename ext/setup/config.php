<?php

declare(strict_types=1);

namespace Shimmie2;

class SetupConfig extends ConfigGroup
{
    public ?string $title = "General";
    public ?int $position = 0;

    #[ConfigMeta("Site title", ConfigType::STRING)]
    public const TITLE = "title";

    #[ConfigMeta("Front page", ConfigType::STRING)]
    public const FRONT_PAGE = "front_page";

    #[ConfigMeta("Main page", ConfigType::STRING)]
    public const MAIN_PAGE = "main_page";

    #[ConfigMeta("Contact URL", ConfigType::STRING)]
    public const CONTACT_LINK = "contact_link";

    #[ConfigMeta("Theme", ConfigType::STRING, options: "Shimmie2\SetupConfig::get_theme_options")]
    public const THEME = "theme";

    #[ConfigMeta("Avatar Size", ConfigType::INT)]
    public const AVATAR_SIZE = "avatar_size";

    #[ConfigMeta("Nice URLs", ConfigType::BOOL, help: "Javascript inactive, can't test Nice URLs!")]
    public const NICE_URLS = "nice_urls";

    /**
     * @return array<string, string>
     */
    public static function get_theme_options(): array
    {
        $themes = [];
        foreach (\Safe\glob("themes/*") as $theme_dirname) {
            assert(is_string($theme_dirname));
            $name = str_replace("themes/", "", $theme_dirname);
            $human = str_replace("_", " ", $name);
            $human = ucwords($human);
            $themes[$human] = $name;
        }
        return $themes;
    }
}
