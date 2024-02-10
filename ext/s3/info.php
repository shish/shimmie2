<?php

declare(strict_types=1);

namespace Shimmie2;

class S3Info extends ExtensionInfo
{
    public const KEY = "s3";

    public string $key = self::KEY;
    public string $name = "S3 CDN Backend";
    public string $url = self::SHIMMIE_URL;
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Push post updates to S3";
}
