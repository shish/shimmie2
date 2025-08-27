<?php

declare(strict_types=1);

namespace Shimmie2;

final class S3Info extends ExtensionInfo
{
    public const KEY = "s3";

    public string $name = "S3 CDN Backend";
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Push media files to S3";
}
