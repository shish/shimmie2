<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A signal that something needs logging
 */
final class LogEvent extends Event
{
    /**
     * a category, normally the extension name
     */
    public string $section;

    /**
     * See python...
     */
    public int $priority = 0;

    /**
     * Free text to be logged
     */
    public string $message;

    /**
     * The time that the event was created
     */
    public int $time;

    /**
     * Extra data to be held separate
     *
     * @var string[]
     */
    public array $args;

    public function __construct(string $section, int $priority, string $message)
    {
        parent::__construct();
        $this->section = $section;
        $this->priority = $priority;
        $this->message = $message;
        $this->time = time();
    }
}
