<?php declare(strict_types=1);

class TipsInfo extends ExtensionInfo
{
    public const KEY = "tips";

    public $key = self::KEY;
    public $name = "Random Tip";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info"];
    public $license = "GPLv2";
    public $description = "Show a random line of text in the subheader space";
    public $documentation = "Formatting is done with HTML";
    public $db_support = [DatabaseDriver::MYSQL, DatabaseDriver::SQLITE];  // rand() ?
}
