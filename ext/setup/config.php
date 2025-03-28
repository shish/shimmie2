<?php

declare(strict_types=1);

namespace Shimmie2;

final class SetupConfig extends ConfigGroup
{
    public const KEY = "setup";
    public ?string $title = "General";
    public ?int $position = 0;

    #[ConfigMeta("Site title", ConfigType::STRING, default: "Shimmie")]
    public const TITLE = "title";

    #[ConfigMeta("Front page", ConfigType::STRING, default: "post/list")]
    public const FRONT_PAGE = "front_page";

    #[ConfigMeta("Main page", ConfigType::STRING, default: "post/list")]
    public const MAIN_PAGE = "main_page";

    #[ConfigMeta("Contact URL", ConfigType::STRING)]
    public const CONTACT_LINK = "contact_link";

    #[ConfigMeta("Theme", ConfigType::STRING, default: "default", options: "Shimmie2\SetupConfig::get_theme_options")]
    public const THEME = "theme";

    #[ConfigMeta("Avatar Size", ConfigType::INT, default: 128)]
    public const AVATAR_SIZE = "avatar_size";

    #[ConfigMeta("Nice URLs", ConfigType::BOOL, default: false, help: "Javascript inactive, can't test Nice URLs!")]
    public const NICE_URLS = "nice_urls";

    #[ConfigMeta(
        "Don't auto-upgrade database",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "Database schema upgrades are no longer automatic; you'll need to run <code>php index.php db-upgrade</code> from the CLI each time you update the code."
    )]
    public const NO_AUTO_DB_UPGRADE = "speed_hax_no_auto_db_upgrade";

    #[ConfigMeta("Cache event listeners", ConfigType::BOOL, default: false, advanced: true)]
    public const CACHE_EVENT_LISTENERS = "speed_hax_cache_listeners";

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

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = parent::get_config_fields();

        // If niceurls are force-enabled at the system level, don't show the option
        foreach ($fields as $key => $field) {
            if (SysConfig::getNiceUrls() && $key === SetupConfig::NICE_URLS) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}
