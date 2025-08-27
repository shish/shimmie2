<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 */

final class RotateImageInfo extends ExtensionInfo
{
    public const KEY = "rotate";

    public string $name = "Rotate Image";
    public array $authors = ["jgen" => "mailto:jgen.tech@gmail.com","Agasa" => "mailto:hiroshiagasa@gmail.com"];
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows admins to rotate images";
    public array $dependencies = [ImageFileHandlerInfo::KEY, ReplaceFileInfo::KEY];
}
