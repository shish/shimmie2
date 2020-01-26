<?php declare(strict_types=1);

/*
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 */

class RotateImageInfo extends ExtensionInfo
{
    public const KEY = "rotate";

    public $key = self::KEY;
    public $name = "Rotate Image";
    public $authors = ["jgen"=>"jgen.tech@gmail.com","Agasa"=>"hiroshiagasa@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows admins to rotate images.";
}
