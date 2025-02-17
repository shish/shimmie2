<?php

declare(strict_types=1);

namespace Shimmie2;

// Checking that various functions work as expected,
// cross different environments and configurations.
class AssumptionsTest extends ShimmiePHPUnitTestCase
{
    public function test_cyrillic_php_lowercase(): void
    {
        // confirm that strtolower does not work with Cyrillic, but mb_strtolower does
        $this->assertNotEquals("советских", strtolower("Советских"), "strtolower");
        $this->assertEquals("советских", mb_strtolower("Советских"), "mb_strtolower");
    }

    public function test_cyrillic_database_lowercase(): void
    {
        global $database;
        $this->assertEquals("советских", $database->get_one("SELECT LOWER('Советских')"), "LOWER");
    }
}
