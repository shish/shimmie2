<?php

/**
 * Name: [Beta] Notes
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Annotate images
 * Documentation:
 */

class NotesInfo extends ExtensionInfo
{
    public const KEY = "notes";

    public $key = self::KEY;
    public $name = "Notes";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Annotate images";
}
