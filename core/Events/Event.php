<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Generic parent class for all events.
 *
 * An event is anything that can be passed around via send_event($blah)
 */
abstract class Event
{
    public bool $stop_processing = false;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return var_export($this, true);
    }
}
