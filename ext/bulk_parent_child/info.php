<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkParentChildInfo extends ExtensionInfo
{
    public const KEY = "bulk_parent_child";

    public string $key = self::KEY;
    public string $name = "Bulk Parent Child";
    public array $authors = ["Flatty" => ""];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Allows bulk setting of parent-child relationships, in order of manual selection";
    public array $dependencies = [BulkActionsInfo::KEY];
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
