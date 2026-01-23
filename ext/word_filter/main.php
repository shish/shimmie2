<?php

declare(strict_types=1);

namespace Shimmie2;

final class WordFilter extends FormatterExtension
{
    public const KEY = "word_filter";

    #[EventListener(priority: 40)] // before emoticon filter
    public function onTextFormatting(TextFormattingEvent $event): void
    {
        parent::onTextFormatting($event);
    }

    public function format(string $text): string
    {
        return $this->filter($text);
    }

    public function strip(string $text): string
    {
        return $this->filter($text);
    }

    private function filter(string $text): string
    {
        $map = $this->get_map();
        foreach ($map as $search => $replace) {
            $search = trim($search);
            $replace = trim($replace);
            if ($search[0] === '/') {
                $text = \Safe\preg_replace($search, $replace, $text);
            } else {
                $search = "/\\b" . str_replace("/", "\\/", $search) . "\\b/i";
                $text = \Safe\preg_replace($search, $replace, $text);
            }
        }
        return $text;
    }

    /**
     * @return string[]
     */
    private function get_map(): array
    {
        $raw = Ctx::$config->get(WordFilterConfig::FILTER) ?? "";
        $lines = explode("\n", $raw);
        $map = [];
        foreach ($lines as $line) {
            $parts = explode(",", $line);
            if (count($parts) === 2) {
                $map[$parts[0]] = $parts[1];
            }
        }
        return $map;
    }
}
