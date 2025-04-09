<?php

declare(strict_types=1);

namespace Shimmie2;

final class TestCaptchaInfo extends ExtensionInfo
{
    public const KEY = "test_captcha";

    public string $key = self::KEY;
    public string $name = "Test Captcha";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "A very simple captcha for testing.";
}
