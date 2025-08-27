<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReCaptchaInfo extends ExtensionInfo
{
    public const KEY = "recaptcha";

    public string $name = "ReCaptcha";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds ReCaptcha to various pages";
}
