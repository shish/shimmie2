<?php declare(strict_types=1);

class TagEditCloudInfo extends ExtensionInfo
{
    public const KEY = "tag_editcloud";

    public string $key = self::KEY;
    public string $name = "Tag EditCloud";
    public array $authors = ["AtomicDryad", "LaureeGrd"];
    public string $description = "Add or remove tags to the editor via clicking.";
}
