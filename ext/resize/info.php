<?php declare(strict_types=1);

/*
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 */

class ResizeImageInfo extends ExtensionInfo
{
    public const KEY = "resize";

    public $key = self::KEY;
    public $name = "Resize Image";
    public $authors = ["jgen"=>"jgen.tech@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "This extension allows admins to resize images.";
    public $version = "0.1";
}
