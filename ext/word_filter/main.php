<?php

declare(strict_types=1);

namespace Shimmie2;

class WordFilter extends Extension
{
    // before emoticon filter
    public function get_priority(): int
    {
        return 40;
    }

    public function onTextFormatting(TextFormattingEvent $event): void
    {
        $event->formatted = $this->filter($event->formatted);
        $event->stripped  = $this->filter($event->stripped);
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Word Filter");
        $sb->add_longtext_option("word_filter");
        $sb->add_label("<br>(each line should be search term and replace term, separated by a comma)");
    }

    private function filter(string $text): string
    {
        $map = $this->get_map();
        foreach ($map as $search => $replace) {
            $search = trim($search);
            $replace = trim($replace);
            if ($search[0] == '/') {
                $text = preg_replace($search, $replace, $text);
            } else {
                $search = "/\\b" . str_replace("/", "\\/", $search) . "\\b/i";
                $text = preg_replace($search, $replace, $text);
            }
        }
        return $text;
    }

    /**
     * @return string[]
     */
    private function get_map(): array
    {
        global $config;
        $raw = $config->get_string("word_filter") ?? "";
        $lines = explode("\n", $raw);
        $map = [];
        foreach ($lines as $line) {
            $parts = explode(",", $line);
            if (count($parts) == 2) {
                $map[$parts[0]] = $parts[1];
            }
        }
        return $map;
    }
}
