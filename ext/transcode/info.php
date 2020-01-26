<?php declare(strict_types=1);

class TranscodeImageInfo extends ExtensionInfo
{
    public const KEY = "transcode";

    public $key = self::KEY;
    public $name = "Transcode Image";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Allows admins to automatically and manually transcode images.";
    public $documentation =
"Can transcode on-demand and automatically on upload. Config screen allows choosing an output format for each of the supported input formats.
Supports GD and ImageMagick. Both support bmp, gif, jpg, png, and webp as inputs, and jpg, png, and lossy webp as outputs.
ImageMagick additionally supports tiff and psd inputs, and webp lossless output.
If and image is unable to be transcoded for any reason, the upload will continue unaffected.";
}
