<?php

declare(strict_types=1);

namespace Shimmie2;

final class LiveFeed extends Extension
{
    public const KEY = "livefeed";

    public function onUserCreation(UserCreationEvent $event): void
    {
        $this->msg("New user created: {$event->username}");
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $user;
        $this->msg(
            make_link("post/view/".$event->image->id)->asAbsolute(). " - ".
            "new post by ".$user->name
        );
    }

    public function onTagSet(TagSetEvent $event): void
    {
        $this->msg(
            make_link("post/view/".$event->image->id)->asAbsolute(). " - ".
            "tags set to: ".Tag::implode($event->new_tags)
        );
    }

    public function onCommentPosting(CommentPostingEvent $event): void
    {
        global $user;
        $this->msg(
            make_link("post/view/".$event->image_id)->asAbsolute(). " - ".
            $user->name . ": " . str_replace("\n", " ", $event->comment)
        );
    }

    public function get_priority(): int
    {
        return 99;
    }

    private function msg(string $data): void
    {
        global $config;

        $host = $config->get_string(LiveFeedConfig::HOST, "127.0.0.1:25252");

        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (!$fp) {
                return;
            }
            fwrite($fp, "$data\n");
            fclose($fp);
        } catch (\Exception $e) {
            /* logging errors shouldn't break everything */
        }
    }
}
