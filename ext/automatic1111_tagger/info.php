<?php

declare(strict_types=1);

namespace Shimmie2;

final class Automatic1111TaggerInfo extends ExtensionInfo
{
    public const KEY = "automatic1111_tagger";

    public string $name = "AI Tagger";
    public array $authors = ["Saetron" => "https://github.com/Saetron/Shimmie2-AI-Tagger"];
    public string $license = self::LICENSE_MIT;
    public string $description = "Connects to the Automatic1111 API to interrogate images and suggest tags.";
    public ?string $documentation = "Adds an 'Interrogate' button to the posts overview, sending images to the Automatic1111 API for tag suggestions.";
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
