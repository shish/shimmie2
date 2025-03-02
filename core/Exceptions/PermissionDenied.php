<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A fairly common, generic exception.
 */
class PermissionDenied extends UserError
{
    public int $http_code = 403;
}
