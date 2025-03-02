<?php

declare(strict_types=1);

namespace Shimmie2;

class UserError extends SCoreException
{
    public int $http_code = 400;
}
