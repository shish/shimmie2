<?php

declare(strict_types=1);

namespace Shimmie2;

// Provides mechanisms for cleanly executing command-line applications
// Was created to try to centralize a solution for whatever caused this:
// quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
final class CommandBuilder
{
    /** @var string[] */
    private array $args = [];

    public function __construct(string $executable)
    {
        if (empty($executable)) {
            throw new \InvalidArgumentException("executable cannot be empty");
        }

        $this->add_args($executable);
    }

    public function add_args(string ...$args): void
    {
        foreach ($args as $arg) {
            $this->args[] = escapeshellarg($arg);
        }
    }

    public function execute(): string
    {
        $cmd = join(" ", $this->args) . " 2>&1";

        $output = [];
        $ret = -1;
        exec($cmd, $output, $ret);

        $output = implode("\n", $output);
        $log_output = empty($output) ? "nothing" : $output;

        Log::debug('command_builder', "Command `$cmd` returned $ret and outputted $log_output");

        if ($ret !== 0) {
            throw new CommandException(command: $cmd, exit_code: $ret, output: $log_output);
        }

        return $output;
    }
}
