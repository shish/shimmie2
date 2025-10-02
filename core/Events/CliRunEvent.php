<?php

declare(strict_types=1);

namespace Shimmie2;

final class CliRunEvent extends Event
{
    public function __construct(
        public \Symfony\Component\Console\Application $app,
        public \Symfony\Component\Console\Input\InputInterface $input,
        public \Symfony\Component\Console\Output\OutputInterface $output,
    ) {
        parent::__construct();
    }
}
