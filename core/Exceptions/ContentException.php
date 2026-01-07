<?php

declare(strict_types=1);

namespace Shimmie2;

class ContentException extends UserError
{
    public int $http_code = 403;
}
