<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 */

class ResizeImageInfo extends ExtensionInfo
{
    public const KEY = "resize";

    public string $key = self::KEY;
    public string $name = "Resize Post";
    public array $authors = ["jgen" => "jgen.tech@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "This extension allows admins to resize images.";
}
