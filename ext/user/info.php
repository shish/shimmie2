<?php declare(strict_types=1);

class UserPageInfo extends ExtensionInfo
{
    public const KEY = "user";

    public $key = self::KEY;
    public $name = "User Management";
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows people to sign up to the website";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
