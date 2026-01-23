<?php

declare(strict_types=1);

namespace Shimmie2;

final class LiveFeed extends Extension
{
    public const KEY = "livefeed";

    #[EventListener(priority: 99)]
    public function onUserCreation(UserCreationEvent $event): void
    {
        $this->msg("New user created: {$event->username}");
    }

    #[EventListener(priority: 99)]
    public function onImageAddition(ImageAdditionEvent $event): void
    {
        $this->msg(
            make_link("post/view/".$event->image->id)->asAbsolute(). " - ".
            "new post by ".Ctx::$user->name
        );
    }

    #[EventListener(priority: 99)]
    public function onTagSet(TagSetEvent $event): void
    {
        $this->msg(
            make_link("post/view/".$event->image->id)->asAbsolute(). " - ".
            "tags set to: ".Tag::implode($event->new_tags)
        );
    }

    #[EventListener(priority: 99)]
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->msg(
            make_link("post/view/".$event->image_id)->asAbsolute(). " - ".
            Ctx::$user->name . ": " . str_replace("\n", " ", $event->comment)
        );
    }

    private function msg(string $data): void
    {
        $host = Ctx::$config->get(LiveFeedConfig::HOST);
        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port);
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
