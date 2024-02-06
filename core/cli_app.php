<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Input\{ArgvInput,InputOption,InputDefinition,InputInterface};
use Symfony\Component\Console\Output\{OutputInterface,ConsoleOutput};

class CliApp extends \Symfony\Component\Console\Application
{
    public function __construct()
    {
        parent::__construct('Shimmie', VERSION);
        $this->setAutoExit(false);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption(
            '--user',
            '-u',
            InputOption::VALUE_REQUIRED,
            'Log in as the given user'
        ));

        return $definition;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        global $user;

        $input ??= new ArgvInput();
        $output ??= new ConsoleOutput();

        if ($input->hasParameterOption(['--user', '-u'])) {
            $name = $input->getParameterOption(['--user', '-u']);
            $user = User::by_name($name);
            if (is_null($user)) {
                die("Unknown user '$name'\n");
            } else {
                send_event(new UserLoginEvent($user));
            }
        }

        $log_level = SCORE_LOG_WARNING;
        if (true === $input->hasParameterOption(['--quiet', '-q'], true)) {
            $log_level = SCORE_LOG_ERROR;
        } else {
            if ($input->hasParameterOption('-vvv', true) || $input->hasParameterOption('--verbose=3', true) || 3 === $input->getParameterOption('--verbose', false, true)) {
                $log_level = SCORE_LOG_DEBUG;
            } elseif ($input->hasParameterOption('-vv', true) || $input->hasParameterOption('--verbose=2', true) || 2 === $input->getParameterOption('--verbose', false, true)) {
                $log_level = SCORE_LOG_DEBUG;
            } elseif ($input->hasParameterOption('-v', true) || $input->hasParameterOption('--verbose=1', true) || $input->hasParameterOption('--verbose', true) || $input->getParameterOption('--verbose', false, true)) {
                $log_level = SCORE_LOG_INFO;
            }
        }
        if (!defined("CLI_LOG_LEVEL")) {
            define("CLI_LOG_LEVEL", $log_level);
        }

        return parent::run($input, $output);
    }
}
