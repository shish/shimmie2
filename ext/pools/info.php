<?php declare(strict_types=1);

class PoolsInfo extends ExtensionInfo
{
    public const KEY = "pools";

    public $key = self::KEY;
    public $name = "Pools System";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info", "jgen"=>"jgen.tech@gmail.com", "Daku"=>"admin@codeanimu.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to create groups of images and order them.";
    public $documentation =
"This extension allows users to created named groups of images, and order the images within the group. Useful for related images like in a comic, etc.";
}
