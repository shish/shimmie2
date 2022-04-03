<?php

declare(strict_types=1);

class FilenameMetadata extends Extension
{
    public function get_priority(): int
    {
        return 40;
    }

    public const FILENAME_REGEX = '/\[(?<username>\w+)-(?<date>[\w:\.-]+)\]-(?<discord_id>\w+)-(?<filename>.+)/';

    public function onImageAddition(ImageAdditionEvent $event)
    {
        if (preg_match(self::FILENAME_REGEX, $event->image->filename, $matches)) {
            if (!is_array($event->image->tag_array)) {
                $event->image->tag_array = [];
            }
            $event->image->tag_array[] = "photographer:".$matches["username"];

            $event->image->posted = $matches["date"];
            $event->image->filename = $matches["filename"];
        }
    }
}
