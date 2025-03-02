<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PermissionGroup extends Enablable
{
    public ?string $title = null;
    public ?int $position = null;
}
