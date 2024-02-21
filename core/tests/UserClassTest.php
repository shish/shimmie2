<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

require_once "core/userclass.php";

class UserClassTest extends ShimmiePHPUnitTestCase
{
    public function test_new_class(): void
    {
        $cls = new UserClass("user2", "user", [
            Permissions::CREATE_COMMENT => true,
            Permissions::BIG_SEARCH => false,
        ]);
        $this->assertEquals("user2", $cls->name);
        $this->assertTrue($cls->can(Permissions::CREATE_COMMENT));
        $this->assertFalse($cls->can(Permissions::BIG_SEARCH));
    }

    public function test_not_found(): void
    {
        $cls = UserClass::$known_classes['user'];
        $this->assertException(ServerError::class, function () use ($cls) {
            $cls->can("not_found");
        });
    }

    public function test_permissions(): void
    {
        $cls = UserClass::$known_classes['user'];
        $ps = $cls->permissions();
        $this->assertContains(Permissions::CREATE_COMMENT, $ps);
    }
}
