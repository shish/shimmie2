<?php

declare(strict_types=1);

namespace Shimmie2;

class VarnishPurger extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(VarnishPurgerConfig::HOST, '127.0.0.1');
        $config->set_default_int(VarnishPurgerConfig::PORT, 80);
        $config->set_default_string(VarnishPurgerConfig::PROTOCOL, 'http');
    }

    private function curl_purge(string $path): void
    {
        // waiting for curl timeout adds ~5 minutes to unit tests
        if (defined("UNITTEST")) {
            return;
        }

        global $config;
        $host = $config->get_string(VarnishPurgerConfig::HOST);
        $port = $config->get_int(VarnishPurgerConfig::PORT);
        $protocol = $config->get_string(VarnishPurgerConfig::PROTOCOL);
        $url = $protocol . '://'. $host . '/' . $path;
        $ch = \Safe\curl_init();
        \Safe\curl_setopt($ch, CURLOPT_URL, $url);
        \Safe\curl_setopt($ch, CURLOPT_PORT, $port);
        \Safe\curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");
        \Safe\curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = \Safe\curl_exec($ch);
        $httpCode = \Safe\curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new ServerError('PURGE ' . $url . ' unsuccessful (HTTP '. $httpCode . ')');
        }
        curl_close($ch);
    }

    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image_id}");
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }

    public function get_priority(): int
    {
        return 99;
    }
}
