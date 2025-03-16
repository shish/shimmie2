<?php

declare(strict_types=1);

namespace Shimmie2;

final class CliGenEvent extends Event
{
    public function __construct(
        public \Symfony\Component\Console\Application $app
    ) {
        parent::__construct();
    }
}
