<?php

declare(strict_types=1);

namespace Shimmie2;

class PMTrigger extends Extension
{
    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $this->send(
            $event->image->owner_id,
            "[System] A post you uploaded has been deleted",
            "Post le gone~ (#{$event->image->id}, {$event->image->get_tag_list()})"
        );
    }

    private function send($to_id, $subject, $body)
    {
        global $user;
        send_event(new SendPMEvent(new PM(
            $user->id,
            get_real_ip(),
            $to_id,
            $subject,
            $body
        )));
    }
}
