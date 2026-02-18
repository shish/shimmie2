<?php

declare(strict_types=1);

namespace Shimmie2;

final class CliRunEvent extends Event
{
    public function __construct(
        public readonly \Symfony\Component\Console\Application $app,
        public readonly \Symfony\Component\Console\Input\InputInterface $input,
        public readonly \Symfony\Component\Console\Output\OutputInterface $output,
    ) {
        parent::__construct();
    }
}
