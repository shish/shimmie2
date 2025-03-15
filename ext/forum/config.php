<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForumConfig extends ConfigGroup
{
    public const KEY = "forum";

    #[ConfigMeta("Title max length", ConfigType::INT, default: 25)]
    public const TITLE_SUBSTRING = "forumTitleSubString";

    #[ConfigMeta("Threads per page", ConfigType::INT, default: 15)]
    public const THREADS_PER_PAGE = "forumThreadsPerPage";

    #[ConfigMeta("Posts per page", ConfigType::INT, default: 15)]
    public const POSTS_PER_PAGE = "forumPostsPerPage";

    #[ConfigMeta("Max chars per post", ConfigType::INT, default: 512)]
    public const MAX_CHARS_PER_POST = "forumMaxCharsPerPost";
}
