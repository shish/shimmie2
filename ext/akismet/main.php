<?php

declare(strict_types=1);

namespace Shimmie2;

final class Akismet extends Extension
{
    public const KEY = "akismet";

    #[EventListener(priority: 31)]
    public function onCheckStringContent(CheckStringContentEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::BYPASS_CONTENT_CHECKS)) {
            return;
        }

        $key = Ctx::$config->get(AkismetConfig::API_KEY);
        if (is_null($key) || $key === "") {
            return;
        }

        // Akismet is designed for blocks of text, not tags or source URLs
        if ($event->type !== StringType::TEXT) {
            return;
        }

        // @phpstan-ignore-next-line
        $akismet = new \Akismet($_SERVER['SERVER_NAME'], $key, [
            'author'       => Ctx::$user->name,
            'email'        => Ctx::$user->email,
            'website'      => '',
            'body'         => $event->content,
            'permalink'    => '',
            'referrer'     => $_SERVER['HTTP_REFERER'] ?? 'none',
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'none',
        ]);

        if ($akismet->errorsExist()) {
            return;
        }

        if ($akismet->isSpam()) {
            throw new ContentException("Akismet thinks that your {$event->type->value} is spam. Try rewriting the {$event->type->value}, or logging in.");
        }
    }
}
