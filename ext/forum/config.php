<?php

declare(strict_types=1);

namespace Shimmie2;

class ForumConfig extends ConfigGroup
{
    public const KEY = "forum";

    #[ConfigMeta("Title max length", ConfigType::INT)]
    public const TITLE_SUBSTRING = "forumTitleSubString";

    #[ConfigMeta("Threads per page", ConfigType::INT)]
    public const THREADS_PER_PAGE = "forumThreadsPerPage";

    #[ConfigMeta("Posts per page", ConfigType::INT)]
    public const POSTS_PER_PAGE = "forumPostsPerPage";

    #[ConfigMeta("Max chars per post", ConfigType::INT)]
    public const MAX_CHARS_PER_POST = "forumMaxCharsPerPost";
}
