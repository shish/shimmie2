<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserClassTest extends ShimmiePHPUnitTestCase
{
    public function test_new_class(): void
    {
        $cls = new UserClass("user2", "user", [
            CommentPermission::CREATE_COMMENT => true,
            IndexPermission::BIG_SEARCH => false,
        ]);
        self::assertEquals("user2", $cls->name);
        self::assertEquals([UserClassSource::UNKNOWN], $cls->sources);
        self::assertTrue($cls->can(CommentPermission::CREATE_COMMENT));
        self::assertFalse($cls->can(IndexPermission::BIG_SEARCH));
    }

    public function test_override_class(): void
    {
        $cls = new UserClass("anonymous", "base", [
            CommentPermission::CREATE_COMMENT => true,
            UserAccountsPermission::CREATE_USER => true,
        ]);
        self::assertEquals([UserClassSource::DEFAULT, UserClassSource::UNKNOWN], $cls->sources);
    }

    public function test_not_found(): void
    {
        $cls = UserClass::$known_classes['user'];
        self::assertException(ServerError::class, function () use ($cls) {
            $cls->can("not_found");
        });
    }

    public function test_permissions(): void
    {
        $cls = UserClass::$known_classes['user'];
        $ps = $cls->permissions();
        self::assertContains(CommentPermission::CREATE_COMMENT, $ps);
    }
}
