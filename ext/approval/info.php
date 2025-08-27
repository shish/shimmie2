<?php

declare(strict_types=1);

namespace Shimmie2;

final class ApprovalInfo extends ExtensionInfo
{
    public const KEY = "approval";

    public string $name = "Approval";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds an approval step to the upload/import process";
}
