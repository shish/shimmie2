<?php declare(strict_types=1);

class LiveFeed extends Extension
{
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Live Feed");
        $sb->add_text_option("livefeed_host", "IP:port to send events to: ");
    }

    public function onUserCreation(UserCreationEvent $event)
    {
        $this->msg("New user created: {$event->username}");
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        global $user;
        $this->msg(
            make_http(make_link("post/view/".$event->image->id))." - ".
            "new post by ".$user->name
        );
    }

    public function onTagSet(TagSetEvent $event)
    {
        $this->msg(
            make_http(make_link("post/view/".$event->image->id))." - ".
            "tags set to: ".Tag::implode($event->tags)
        );
    }

    public function onCommentPosting(CommentPostingEvent $event)
    {
        global $user;
        $this->msg(
            make_http(make_link("post/view/".$event->image_id))." - ".
            $user->name . ": " . str_replace("\n", " ", $event->comment)
        );
    }

    public function get_priority(): int
    {
        return 99;
    }

    private function msg(string $data)
    {
        global $config;

        $host = $config->get_string("livefeed_host", "127.0.0.1:25252");

        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (! $fp) {
                return;
            }
            fwrite($fp, "$data\n");
            fclose($fp);
        } catch (Exception $e) {
            /* logging errors shouldn't break everything */
        }
    }
}
