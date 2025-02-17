<?php

declare(strict_types=1);

namespace Shimmie2;

class PostTitlesConfig extends ConfigGroup
{
    public const KEY = "post_titles";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_post_titles_version";

    #[ConfigMeta("Default to filename", ConfigType::BOOL, default: false)]
    public const DEFAULT_TO_FILENAME = "post_titles_default_to_filename";

    #[ConfigMeta("Show in window title", ConfigType::BOOL, default: false)]
    public const SHOW_IN_WINDOW_TITLE = "post_titles_show_in_window_title";
}
