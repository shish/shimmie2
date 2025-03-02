<?php

declare(strict_types=1);

namespace Shimmie2;

class ServerError extends SCoreException
{
    public int $http_code = 500;
}
