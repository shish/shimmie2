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

        if (!$can_use_html && stripos($event->wikipage->body, '[html]') !== false) {
            throw new UserError("You do not have permission to use [html] tags.");
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

        if (stripos($formatted, '[html]') === false) {
            return;
        }

        $result = "";
        $offset = 0;

        $block_elements = 'ul|ol|li|div|h[1-6]|details|summary|blockquote|table|tr|td|th';

        while (($start = stripos($formatted, '[html]', $offset)) !== false) {
            $end = stripos($formatted, '[/html]', $start);

            if ($end === false) {
                break;
            }

            $result .= substr($formatted, $offset, $start - $offset);
            $inner_content = substr($formatted, $start + 6, $end - ($start + 6));

            $decoded = htmlspecialchars_decode($inner_content, ENT_QUOTES);

            $decoded = preg_replace('/(<\/?(?:' . $block_elements . ')[^>]*>)\s*<br\s*\/?>/i', '$1', $decoded) ?? $decoded;
            $decoded = preg_replace('/<br\s*\/?>\s*(<\/?(?:' . $block_elements . ')[^>]*>)/i', '$1', $decoded) ?? $decoded;
            $decoded = preg_replace('/<p>\s*(<\/?(?:' . $block_elements . ')[^>]*>)\s*<\/p>/i', '$1', $decoded) ?? $decoded;

            $result .= $decoded;
            $offset = $end + 7;
        }

        $result .= substr($formatted, $offset);
        $event->formatted = $result;
    }
}
