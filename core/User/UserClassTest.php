<?php

declare(strict_types=1);

namespace Shimmie2;

class UserClassTest extends ShimmiePHPUnitTestCase
{
    public function test_new_class(): void
    {
        $cls = new UserClass("user2", "user", [
            CommentPermission::CREATE_COMMENT => true,
            IndexPermission::BIG_SEARCH => false,
        ]);
        $this->assertEquals("user2", $cls->name);
        $this->assertTrue($cls->can(CommentPermission::CREATE_COMMENT));
        $this->assertFalse($cls->can(IndexPermission::BIG_SEARCH));
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
        $this->assertContains(CommentPermission::CREATE_COMMENT, $ps);
    }
}
