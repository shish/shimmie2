<?php

declare(strict_types=1);

namespace Shimmie2;

class HomeConfig extends ConfigGroup
{
    public ?string $title = "Home Page";

    #[ConfigMeta("Page links", ConfigType::STRING, ui_type: "longtext", help: "Use BBCode, leave blank for defaults")]
    public const HOME_LINKS = 'home_links';

    #[ConfigMeta("Page text", ConfigType::STRING, ui_type: "longtext")]
    public const HOME_TEXT = 'home_text';

    #[ConfigMeta("Counter", ConfigType::STRING, options: "Shimmie2\HomeConfig::get_counter_options")]
    public const HOME_COUNTER = 'home_counter';

    /**
     * @return array<string, string>
     */
    public static function get_counter_options(): array
    {
        $counters = [];
        $counters["None"] = "none";
        $counters["Text-only"] = "text-only";
        foreach (\Safe\glob("ext/home/counters/*") as $counter_dirname) {
            $name = str_replace("ext/home/counters/", "", $counter_dirname);
            $counters[ucfirst($name)] = $name;
        }
        return $counters;
    }
}
