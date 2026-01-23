<?php

declare(strict_types=1);

namespace Shimmie2;

final class BanWords extends Extension
{
    public const KEY = "ban_words";

    #[EventListener(priority: 30)]
    public function onCheckStringContent(CheckStringContentEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::BYPASS_CONTENT_CHECKS)) {
            return;
        }

        $comment = mb_strtolower($event->content);

        foreach (self::get_words() as $word) {
            if ($word[0] === '/') {
                // lines that start with slash are regex
                if (\Safe\preg_match($word, $comment)) {
                    throw new ContentException("{$event->type->value} contains banned terms");
                }
            } else {
                // other words are literal
                if (str_contains($comment, $word)) {
                    throw new ContentException("{$event->type->value} contains banned terms");
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public static function get_words(): array
    {
        $words = [];

        $banned = Ctx::$config->get(BanWordsConfig::BANNED_WORDS);
        foreach (explode("\n", $banned) as $word) {
            $word = trim(mb_strtolower($word));
            if (strlen($word) === 0) {
                // line is blank
                continue;
            }
            $words[] = $word;
        }

        return $words;
    }
}
