<?php

declare(strict_types=1);

namespace Shimmie2;

class AliasEditorInfo extends ExtensionInfo
{
    public const KEY = "alias_editor";

    public string $key = self::KEY;
    public string $name = "Alias Editor";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Edit the alias list";
    public ?string $documentation = 'The list is visible at <a href="$site/alias/list">/alias/list</a>; only site admins can edit it, other people can view and download it';
    public bool $core = true;
}
