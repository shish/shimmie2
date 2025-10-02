<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A wake-up call for extensions.
 *
 * This event is sent before $user is set to anything
 *
 * This event also collects shutdown handlers, which bypass the
 * regular Event system so that they can be used to log Event
 * metrics without altering the system they measure.
 */
final class InitExtEvent extends Event
{
    /** @var array<callable> */
    private array $shutdown_handlers = [];

    /**
     * Register a function to be called at the end of the request.
     *
     * The function should take no arguments and return nothing.
     *
     * @param callable $handler The function to be called at the end of the request.
     */
    public function add_shutdown_handler(callable $handler): void
    {
        $this->shutdown_handlers[] = $handler;
    }

    public function run_shutdown_handlers(): void
    {
        foreach ($this->shutdown_handlers as $handler) {
            try {
                $handler();
            } catch (\Throwable $e) {
                error_log("Shutdown handler failed: " . $e->getMessage());
            }
        }
    }
}
