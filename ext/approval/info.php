<?php

declare(strict_types=1);

namespace Shimmie2;

class ApprovalInfo extends ExtensionInfo
{
    public const KEY = "approval";

    public string $key = self::KEY;
    public string $name = "Approval";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds an approval step to the upload/import process.";
}
