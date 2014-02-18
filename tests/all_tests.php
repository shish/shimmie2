<?php
/**
 * SimpleTest integration with Travis CI for Shimmie
 * 
 * @package    Shimmie
 * @author     jgen <jeffgenovy@gmail.com>
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @copyright  Copyright (c) 2014, jgen
 */

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

require_once('test_install.php');

$options = getopt("d:");
$db = $options["d"];

if (empty($db)){ die("Error: need to specifiy a database for the test environment."); }

define("_TRAVIS_DATABASE", $db);

$test_suite = new TestSuite('Shimmie tests');
$test_suite->add(new ShimmieInstallerTest());


//$tr = new TextReporter();
//$test_suite->run( $tr );
