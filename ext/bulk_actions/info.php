<?php declare(strict_types=1);

class BulkActionsInfo extends ExtensionInfo
{
    public const KEY = "bulk_actions";

    public string $key = self::KEY;
    public string $name = "Bulk Actions";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides query and selection-based bulk action support";
    public ?string $documentation = "Provides bulk action section in list view. Allows performing actions against a set of posts based on query or manual selection. Based on Mass Tagger by Christian Walde <walde.christian@googlemail.com>, contributions by Shish and Agasa.";
}
