<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Input\{ArgvInput, InputInterface};
use Symfony\Component\Console\Output\{ConsoleOutput, OutputInterface};

final class CliApp extends \Symfony\Component\Console\Application
{
    public function __construct()
    {
        parent::__construct('Shimmie', SysConfig::getVersion());
        $this->setAutoExit(false);
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $input ??= new ArgvInput();
        $output ??= new ConsoleOutput();
        send_event(new CliRunEvent($this, $input, $output));

        return parent::run($input, $output);
    }
}
