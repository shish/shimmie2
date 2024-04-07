<?php

declare(strict_types=1);

namespace Shimmie2;

class StatisticsInfo extends ExtensionInfo
{
    public const KEY = "statistics";

    public string $key = self::KEY;
    public string $name = "Statistics";
    public array $authors = ["Discomrade" => ""];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public string $description = "Displays a user statistics page, similar to booru.org. Read the documentation before enabling.";
    public ?string $documentation =
"This will display certain user statistics, depending on which extensions are enabled. The taggers statistic relies on the Tag History extension, so it will only count from when that extension was enabled.\n
Assuming the extension is enabled, statistics are shown for user uploads, comments, tags (requires Tag History), notes, sources (requires Source History), favorites and forum posts.\n
Tags statistics count both removing and adding tags, so changing 'tag_me' to 'tagme' counts as both a deletion and an addition, 2 tag edits. This is different to how booru.org calculates their tag statistics (which seems to only count the number of changes submitted).";
}
