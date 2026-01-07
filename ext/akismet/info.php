<?php

declare(strict_types=1);

namespace Shimmie2;

final class AkismetInfo extends ExtensionInfo
{
    public const KEY = "akismet";

    public string $name = "Akismet";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Akismet spam detection for comments and other user content";
    public ?string $documentation =
        "This extension uses the Akismet service to detect spam in comments, forum posts, and other user-generated content.
<p>To use this extension, you need to sign up for an Akismet API key at <a href='https://akismet.com/'>akismet.com</a>.
Once you have your API key, enter it in the configuration settings.
<p>Note: This extension makes external API calls to Akismet's servers, so it may add latency to content submission.";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
