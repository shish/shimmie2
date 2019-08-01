<?php

/**
 * Name: Random Tip
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Show a random line of text in the subheader space
 * Documentation:
 *  Formatting is done with HTML
 */


class TipsInfo extends ExtensionInfo
{
    public $key = "tips";
    public $name = "Random Tip";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info"];
    public $license = "GPLv2";
    public $description = "Show a random line of text in the subheader space";
    public $documentation = "Formatting is done with HTML";
    public $db_support = [DatabaseDriver::MYSQL, DatabaseDriver::SQLITE];
}