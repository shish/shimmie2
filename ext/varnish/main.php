<?php

declare(strict_types=1);

namespace Shimmie2;

class VarnishPurger extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string('varnish_host', '127.0.0.1');
        $config->set_default_int('varnish_port', 80);
        $config->set_default_string('varnish_protocol', 'http');
    }

    private function curl_purge(string $path): void
    {
        // waiting for curl timeout adds ~5 minutes to unit tests
        if (defined("UNITTEST")) {
            return;
        }

        global $config;
        $host = $config->get_string('varnish_host');
        $port = $config->get_int('varnish_port');
        $protocol = $config->get_string('varnish_protocol');
        $url = $protocol . '://'. $host . '/' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
