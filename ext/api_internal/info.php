<?php declare(strict_types=1);


/*
 * Name: Autocomplete
 * Author: Daku <admin@codeanimu.net>
 * Description: Adds autocomplete to search & tagging.
 */

class ApiInternalInfo extends ExtensionInfo
{
    public const KEY = "api_internal";

    public $key = self::KEY;
    public $name = "Internal API";
    public $authors = ["Daku"=>"admin@codeanimu.net", "Matthew Barbour"=>"matthew@darkholme.net"];
    public $description = "Dependency extension used to provide a standardized source for performing operations via an API";
    public $visibility = self::VISIBLE_HIDDEN;
}
