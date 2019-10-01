<?php

/*
 * Name: Logging (Logstash)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Send log events to a network port.
 * Visibility: admin
 */

class LogLogstashInfo extends ExtensionInfo
{
    public const KEY = "log_logstash";

    public $key = self::KEY;
    public $name = "Logging (Logstash)";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Send log events to a network port.";
    public $visibility = self::VISIBLE_ADMIN;
}
