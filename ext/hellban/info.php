<?php declare(strict_types=1);

/*
 * Name: [Beta] Hellban
 */

class HellBanInfo extends ExtensionInfo
{
    public const KEY = "hellban";

    public string $key = self::KEY;
    public string $name = "Hellban";
    public bool $beta = true;
    public string $description = "Make some users only visible to themselves";
}
