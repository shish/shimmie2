<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagHistoryInfo extends ExtensionInfo
{
    public const KEY = "tag_history";

    public string $name = "Tag History";
    public array $authors = ["Bzchan" => "mailto:bzchan@animemahou.com","jgen" => "mailto:jgen.tech@gmail.com"];
    public string $description = "Keep a record of tag changes, and allows you to revert changes";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
