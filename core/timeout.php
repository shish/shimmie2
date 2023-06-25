<?php

class TimeoutException extends RuntimeException
{
}

class Timeout
{
    private $active;

    public function set($seconds)
    {
        $this->active = true;
        // declare(ticks = 1);
        // pcntl_signal(SIGALRM, [$this, 'handle'], true);
        // pcntl_alarm($seconds);
        set_time_limit($seconds + 5);
    }

    public function clear()
    {
        set_time_limit(0);
        $this->active = false;
    }

    public function handle($signal)
    {
        if ($this->active) {
            throw new TimeoutException();
        }
    }
}
