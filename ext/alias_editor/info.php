<?php declare(strict_types=1);

class AliasEditorInfo extends ExtensionInfo
{
    public const KEY = "alias_editor";

    public $key = self::KEY;
    public $name = "Alias Editor";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Edit the alias list";
    public $documentation = 'The list is visible at <a href="$site/alias/list">/alias/list</a>; only site admins can edit it, other people can view and download it';
    public $core = true;
}
