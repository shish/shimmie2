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
 *
 * @template TThemelet of Themelet = Themelet
 */
abstract class Extension extends Enablable
{
    /** @var TThemelet $theme */
    protected Themelet $theme;
    public const VERSION_KEY = null;

    public function __construct()
    {
        // When we say "$theme should be any Themelet", phpstan interprets
        // that as "subclass of Themelet, but not the parent class"...
        // @phpstan-ignore-next-line
        $this->theme = Themelet::get_for_extension_class(get_called_class());
    }

    protected function get_version(): int
    {
        $name = static::VERSION_KEY ?? "ext_" . static::KEY . "_version";
        return Ctx::$config->get($name, ConfigType::INT) ?? 0;
    }

    protected function set_version(int $ver): void
    {
        $name = static::VERSION_KEY ?? "ext_" . static::KEY . "_version";
        Ctx::$config->set($name, $ver);
        Log::info("upgrade", "Set version for $name to $ver");
    }
}
