<?php

declare(strict_types=1);

namespace Shimmie2;

class ObjectNotFound extends UserError
{
    public int $http_code = 404;
}
