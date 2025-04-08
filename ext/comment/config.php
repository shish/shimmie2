<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentConfig extends ConfigGroup
{
    public const KEY = "comment";

    #[ConfigMeta("Limit to N comments per (window) minutes", ConfigType::INT, default: 10)]
    public const LIMIT = "comment_limit";

    #[ConfigMeta("Comment limit window (minutes)", ConfigType::INT, default: 5)]
    public const WINDOW = "comment_window";

    #[ConfigMeta("Show comments on post/list", ConfigType::INT, default: 5)]
    public const COUNT = "comment_count";

    #[ConfigMeta("Comments per post on comments/list", ConfigType::INT, default: 10)]
    public const LIST_COUNT = "comment_list_count";

    #[ConfigMeta("Akismet API key", ConfigType::STRING)]
    public const WORDPRESS_KEY = "comment_wordpress_key";

    #[ConfigMeta("Show repeat anons publicly", ConfigType::BOOL, default: false)]
    public const SHOW_REPEAT_ANONS = "comment_samefags_public";

    #[ConfigMeta(
        "List only recent comments",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "Only comments from the past 24 hours show up in <code>/comment/list</code>",
    )]
    public const RECENT_COMMENTS = "speed_hax_recent_comments";
}
