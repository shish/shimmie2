<?php

declare(strict_types=1);

namespace Shimmie2;

final class TestCaptchaInfo extends ExtensionInfo
{
    public const KEY = "test_captcha";

    public string $name = "Test Captcha";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "A very simple captcha for testing";
}
