<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReportImageInfo extends ExtensionInfo
{
    public const KEY = "report_image";

    public string $name = "Report Posts";
    public array $authors = ["ATravelingGeek" => "mailto:atg@atravelinggeek.com"];
    public string $description = "Report posts as dupes/illegal/etc";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
