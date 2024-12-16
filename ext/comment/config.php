<?php

declare(strict_types=1);

namespace Shimmie2;

class CommentConfig extends ConfigGroup
{
    public const VERSION = "ext_comments_version";
    public const COUNT = "comment_count";
    public const WINDOW = "comment_window";
    public const LIMIT = "comment_limit";
    public const LIST_COUNT = "comment_list_count";
    public const CAPTCHA = "comment_captcha";
    public const WORDPRESS_KEY = "comment_wordpress_key";
    public const SHOW_REPEAT_ANONS = "comment_samefags_public";
}
