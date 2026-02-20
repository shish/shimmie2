<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiHtml extends Extension
{
    public const KEY = "wiki_html";

    #[EventListener(priority: 10)]
    public function onWikiUpdate(WikiUpdateEvent $event): void
    {
        $can_use_html = Ctx::$user->can(WikiHtmlPermission::USE_HTML) || Ctx::$user->can(WikiPermission::ADMIN);

        if (!$can_use_html) {
            $event->wikipage->body = str_ireplace(['[html]', '[/html]'], ['&#91;html&#93;', '&#91;/html&#93;'], $event->wikipage->body);
        }
    }

    #[EventListener(priority: 10)]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $is_wiki_save = $event->page_matches("wiki/{title}/{action}") && $event->get_arg('action') === 'save';
            $can_use_html = Ctx::$user->can(WikiHtmlPermission::USE_HTML) || Ctx::$user->can(WikiPermission::ADMIN);

            if (!$can_use_html || !$is_wiki_save) {
                array_walk_recursive($_POST, function (mixed &$value): void {
                    if (is_string($value) && stripos($value, '[html]') !== false) {
                        $value = str_ireplace(['[html]', '[/html]'], ['&#91;html&#93;', '&#91;/html&#93;'], $value);
                    }
                });
            }
        }
    }

    #[EventListener(priority: 90)]
    public function onTextFormatting(TextFormattingEvent $event): void
    {
        $formatted = $event->formatted ?? "";

        $result = preg_replace_callback(
            '/\[html\](.*?)\[\/html\]/is',
            function (array $matches): string {
                $html = htmlspecialchars_decode($matches[1], ENT_QUOTES);

                $html = preg_replace('/<br\s*\/?>/i', '', $html) ?? $html;
                $html = preg_replace('/<\/?p>/i', '', $html) ?? $html;

                return $html;
            },
            $formatted
        );

        $event->formatted = $result ?? $formatted;
    }
}
