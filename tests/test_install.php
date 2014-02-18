<?php
/**
 * SimpleTest integration with Travis CI for Shimmie
 * 
 * @package    Shimmie
 * @author     jgen <jeffgenovy@gmail.com>
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @copyright  Copyright (c) 2014, jgen
 */

class ShimmieInstallerTest extends WebTestCase {
	function testInstallShimmie()
	{
		$db = constant("_TRAVIS_DATABASE");
		
		// Make sure that we know what database to use.
		$this->assertFalse(empty($db));
		
		$this->get('http://127.0.0.1/');
		$this->assertResponse(200);
		$this->assertTitle("Shimmie Installation");
		$this->assertText("Database Install");
		
		$this->setField("database_type", $db);
		$this->assertField("database_host", "localhost");
		
		if ($db === "mysql") {
			$this->setField("database_user", "root");
			$this->setField("database_password", "");
		} elseif ($db === "pgsql") {
			$this->setField("database_user", "postgres");
			$this->setField("database_password", "");
		}		
		
		$this->assertField("database_name", "shimmie");
		$this->clickSubmit("Go!");
		
		if (!$this->assertText("Installation Succeeded!")) {
			$this->showSource();
		}
	}
}
