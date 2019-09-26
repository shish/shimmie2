<?php

/*
 * Name: Resize Image
 * Author: jgen <jgen.tech@gmail.com>
 * Description: Allows admins to resize images.
 * License: GPLv2
 * Version: 0.1
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 * Documentation:
 *  This extension allows admins to resize images.
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
