<?php declare(strict_types=1);

class NotesInfo extends ExtensionInfo
{
    public const KEY = "notes";

    public $key = self::KEY;
    public $name = "Notes";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Annotate images";
}
