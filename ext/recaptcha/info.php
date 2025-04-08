<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReCaptchaInfo extends ExtensionInfo
{
    public const KEY = "recaptcha";

    public string $key = self::KEY;
    public string $name = "ReCaptcha";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds ReCaptcha to various pages.";
}
