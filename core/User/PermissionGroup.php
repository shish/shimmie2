<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PermissionGroup
{
    use Enablable;
    public const KEY = null;

    public ?string $title = null;
    public ?int $position = null;
}
