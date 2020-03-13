<?php declare(strict_types=1);

class VarnishPurger extends Extension
{
    private function curl_purge($path)
    {
        // waiting for curl timeout adds ~5 minutes to unit tests
        if (defined("UNITTEST")) {
            return;
        }

        $url = make_http(make_link($path));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        assert(!is_null($result) && !is_null($httpCode));
        //return $result;
    }

    public function onCommentPosting(CommentPostingEvent $event)
    {
        $this->curl_purge("post/view/{$event->image_id}");
    }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $this->curl_purge("post/view/{$event->image->id}");
    }

    public function get_priority(): int
    {
        return 99;
    }
}
