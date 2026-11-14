<?php

declare(strict_types=1);

namespace Shimmie2;

use Automattic\Akismet\Akismet as AkismetClient;
use Automattic\Akismet\DTO\Content;
use Automattic\Akismet\Enum\ContentType;
use Automattic\Akismet\Exception\AkismetException;

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

        try {
            $akismet = AkismetClient::create(
                apiKey: $key,
                site: (string) Url::base()->asAbsolute(),
            );
            $result = $akismet->check(new Content(
                userIp: (string)Network::get_real_ip(),
                userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
                body: $event->content,
                authorName: Ctx::$user->name,
                authorEmail: Ctx::$user->email,
                type: ContentType::Comment,
                referrer: $_SERVER['HTTP_REFERER'] ?? null,
            ));
        } catch (AkismetException $e) {
            Log::warning("akismet", "Akismet exception: " . $e->getMessage());
            return;
        }

        if ($result->isSpam()) {
            throw new ContentDenied("Akismet thinks that your {$event->type->value} is spam. Try rewriting the {$event->type->value}, or logging in.");
        }
    }
}
