<?php declare(strict_types=1);

use GeoIp2\Database\Reader;

class GeoBan extends Extension
{
    /** @var IPBanTheme */
    protected $theme;

    public function get_priority(): int
    {
        return 10;
    }

    public function onUserLogin(UserLoginEvent $event)
    {

		$t = microtime(true);
		$reader = new Reader('data/GeoLite2-Country.mmdb');
		$record = $reader->country($_SERVER["REMOTE_ADDR"]);
		# print($record->country->isoCode . "\n"); // 'US'
		# print($record->country->name . "\n"); // 'United States'
		# print(microtime(true) - $t . "\n");

		$banned = ["IE"];

		if(in_array($record->country->isoCode, $banned)) {
			header("HTTP/1.0 403 Forbidden");
			print "This country is banned";
			exit;
        }
    }
}
