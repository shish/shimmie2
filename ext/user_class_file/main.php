<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserClassFileManager extends Extension
{
    public const KEY = "user_class_file";

    public function onInitExt(InitExtEvent $event): void
    {
        UserClass::$loading = UserClassSource::FILE;
        @include_once "data/config/user-classes.conf.php";
        UserClass::$loading = UserClassSource::UNKNOWN;
    }

    public function get_priority(): int
    {
        return 60; // After `user` extension loads the default classes
    }
}
