<?php

declare(strict_types=1);

namespace Shimmie2;

final class VarnishPurger extends Extension
{
    public const KEY = "varnish";

    private function curl_purge(string $path): void
    {
        // waiting for curl timeout adds ~5 minutes to unit tests
        if (defined("UNITTEST")) {
            return;
        }

        $host = Ctx::$config->get(VarnishPurgerConfig::HOST);
        $port = Ctx::$config->get(VarnishPurgerConfig::PORT);
        $protocol = Ctx::$config->get(VarnishPurgerConfig::PROTOCOL);
        $url = $protocol . '://'. $host . '/' . $path;
        $ch = \Safe\curl_init();
        \Safe\curl_setopt($ch, CURLOPT_URL, $url);
        \Safe\curl_setopt($ch, CURLOPT_PORT, $port);
        \Safe\curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");
        \Safe\curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = \Safe\curl_exec($ch);
        $httpCode = \Safe\curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new ServerError('PURGE ' . $url . ' unsuccessful (HTTP '. $httpCode . ')');
        }
        curl_close($ch);
    }

    #[EventListener(priority: 99)]
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image_id}");
    }

    #[EventListener(priority: 99)]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }

    #[EventListener(priority: 99)]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }
}
