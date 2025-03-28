<?php

declare(strict_types=1);

namespace Shimmie2;

final readonly class Path
{
    public function __construct(private string $name)
    {
    }

    public function exists(): bool
    {
        return file_exists($this->name);
    }

    public function is_dir(): bool
    {
        return is_dir($this->name);
    }

    public function is_file(): bool
    {
        return is_file($this->name);
    }

    public function is_readable(): bool
    {
        return is_readable($this->name);
    }

    public function unlink(): void
    {
        \Safe\unlink($this->name);
    }

    public function rmdir(): void
    {
        \Safe\rmdir($this->name);
    }

    public function mkdir(int $permissions = 0777, bool $recursive = false): void
    {
        \Safe\mkdir($this->name, $permissions, $recursive);
    }

    /**
     * @return 0|positive-int
     */
    public function filesize(): int
    {
        return \Safe\filesize($this->name);
    }

    public function filemtime(): int
    {
        return \Safe\filemtime($this->name);
    }

    /**
     * @param string|resource $contents
     */
    public function put_contents($contents): void
    {
        \Safe\file_put_contents($this->name, $contents);
    }

    public function get_contents(): string
    {
        return \Safe\file_get_contents($this->name);
    }

    public function basename(): Path
    {
        return new Path(basename($this->name));
    }

    public function dirname(): Path
    {
        return new Path(dirname($this->name));
    }

    public function absolute(): Path
    {
        return new Path(\FFSPHP\Paths::abspath($this->name));
    }

    public function relative_to(Path $base): Path
    {
        return new Path(\FFSPHP\Paths::relative_path($this->absolute()->str(), $base->absolute()->str()));
    }

    /**
     * @return hash-string
     */
    public function md5(): string
    {
        return \Safe\md5_file($this->name);
    }

    public function copy(Path $dest): void
    {
        \Safe\copy($this->str(), $dest->str());
    }

    public function rename(Path $dest): void
    {
        \Safe\rename($this->str(), $dest->str());
    }

    public function str(): string
    {
        return $this->name;
    }
}
