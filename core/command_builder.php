<?php

declare(strict_types=1);

namespace Shimmie2;

// Provides mechanisms for cleanly executing command-line applications
// Was created to try to centralize a solution for whatever caused this:
// quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
class CommandBuilder
{
    private string $executable;
    /** @var string[] */
    private array $args = [];
    /** @var string[] */
    public array $output;

    public function __construct(string $executable)
    {
        if (empty($executable)) {
            throw new \InvalidArgumentException("executable cannot be empty");
        }

        $this->executable = $executable;
    }

    public function add_flag(string $value): void
    {
        $this->args[] = $value;
    }

    public function add_escaped_arg(string $value): void
    {
        $this->args[] = escapeshellarg($value);
    }

    public function generate(): string
    {
        $command = escapeshellarg($this->executable);
        if (!empty($this->args)) {
            $command .= " ";
            $command .= join(" ", $this->args);
        }

        return escapeshellcmd($command)." 2>&1";
    }

    public function combineOutput(string $empty_output = ""): string
    {
        if (empty($this->output)) {
            return $empty_output;
        } else {
            return implode("\r\n", $this->output);
        }
    }

    public function execute(bool $fail_on_non_zero_return = false): int
    {
        $cmd = $this->generate();
        exec($cmd, $this->output, $ret);

        $output = $this->combineOutput("nothing");

        log_debug('command_builder', "Command `$cmd` returned $ret and outputted $output");

        if ($fail_on_non_zero_return && (int)$ret !== (int)0) {
            throw new ServerError("Command `$cmd` failed, returning $ret and outputting $output");
        }
        return $ret;
    }
}
