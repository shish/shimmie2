<?php

declare(strict_types=1);

namespace Shimmie2;

class InvalidInput extends UserError
{
    public int $http_code = 402;
}
