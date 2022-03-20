<?php

declare(strict_types=1);

class FilenameMetadataInfo extends ExtensionInfo
{
    public const KEY = "filename_metadata";

    public string $key = self::KEY;
    public string $name = "Metadata from Filename";
    public array $authors = ["Jessica Stokes"=>"hello@jessicastokes.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Import metadata from images' file names";
}
