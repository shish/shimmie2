<?php declare(strict_types=1);

class ForumInfo extends ExtensionInfo
{
    public const KEY = "forum";

    public string $key = self::KEY;
    public string $name = "Forum";
    public array $authors = ["Sein Kraft"=>"mail@seinkraft.info","Alpha"=>"alpha@furries.com.ar"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Rough forum extension";
}
