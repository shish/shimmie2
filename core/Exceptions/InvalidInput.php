<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * For validate_input()
 */
class InvalidInput extends UserError
{
    public int $http_code = 402;
}
