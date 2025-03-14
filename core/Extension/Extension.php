<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * An Extension is a class that can be loaded by the system to add new
 * functionality. It can hook into events, and provide new features.
 *
 * send_event(BlahEvent()) -> onBlah($event)
 *
 * Also loads the theme object into $this->theme if available
 *
 * The original concept came from Artanis's Extension extension
 * --> https://github.com/Artanis/simple-extension/tree/master
 * Then re-implemented by Shish after he broke the forum and couldn't
 * find the thread where the original was posted >_<
 */
abstract class Extension extends Enablable
{
    protected Themelet $theme;
    public const VERSION_KEY = null;

    public function __construct()
    {
        $this->theme = Themelet::get_for_extension_class(get_called_class());
    }

    /**
     * Override this to change the priority of the extension,
     * lower numbered ones will receive events first.
     */
    public function get_priority(): int
    {
        return 50;
    }

    protected function get_version(): int
    {
        global $config;
        $name = static::VERSION_KEY ?? "ext_" . static::KEY . "_version";
        return $config->get_int($name, 0);
    }

    protected function set_version(int $ver): void
    {
        global $config;
        $name = static::VERSION_KEY ?? "ext_" . static::KEY . "_version";
        $config->set_int($name, $ver);
        Log::info("upgrade", "Set version for $name to $ver");
    }
}
