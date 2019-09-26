<?php

/*
 * Name: [Beta] Oekaki
 * Author: Shish
 * Description: ChibiPaint-based Oekaki uploader
 */

class OekakiInfo extends ExtensionInfo
{
    public const KEY = "oekaki";

    public $key = self::KEY;
    public $name = "Oekaki";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "ChibiPaint-based Oekaki uploader";
    public $beta = true;
}
