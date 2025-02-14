<?php

declare(strict_types=1);

namespace Shimmie2;

class CommentConfig extends ConfigGroup
{
    public const KEY = "comment";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_comments_version";

    #[ConfigMeta("Limit to N comments per (window) minutes", ConfigType::INT)]
    public const LIMIT = "comment_limit";

    #[ConfigMeta("Comment limit window (minutes)", ConfigType::INT)]
    public const WINDOW = "comment_window";

    #[ConfigMeta("Show comments on post/list", ConfigType::INT)]
    public const COUNT = "comment_count";

    #[ConfigMeta("Comments per post on comments/list", ConfigType::INT)]
    public const LIST_COUNT = "comment_list_count";

    #[ConfigMeta("Akismet API key", ConfigType::STRING)]
    public const WORDPRESS_KEY = "comment_wordpress_key";

    #[ConfigMeta("Require CAPTCHA for anonymous comments", ConfigType::BOOL)]
    public const CAPTCHA = "comment_captcha";

    #[ConfigMeta("ReCAPTCHA secret key", ConfigType::STRING)]
    public const RECAPTCHA_PRIVKEY = "api_recaptcha_privkey";

    #[ConfigMeta("ReCAPTCHA site key", ConfigType::STRING)]
    public const RECAPTCHA_PUBKEY = "api_recaptcha_pubkey";

    #[ConfigMeta("Show repeat anons publicly", ConfigType::BOOL)]
    public const SHOW_REPEAT_ANONS = "comment_samefags_public";
}
