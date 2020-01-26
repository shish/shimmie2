<?php declare(strict_types=1);

class ArtistsInfo extends ExtensionInfo
{
    public const KEY = "artists";

    public $key = self::KEY;
    public $name = "Artists System";
    public $url = self::SHIMMIE_URL;
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info","Alpha"=>"alpha@furries.com.ar"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Simple artists extension";
    public $beta = true;
}
