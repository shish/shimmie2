<?php declare(strict_types=1);

class JsonRpcInfo extends ExtensionInfo
{
    public const KEY = "json_rpc";

    public $key = self::KEY;
    public $name = "JSON RPC API";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "An entry point for a JSON RPC interface";
}
