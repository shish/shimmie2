<?php

declare(strict_types=1);

class FilenameMetadata extends Extension
{
    public function get_priority(): int
    {
        return 40;
    }

    const FILENAME_REGEX = '/\[(?<username>\w+)-(?<date>[\w-]+)\]-(?<discord_id>\w+)-(?<filename>.+)/';

    public function onImageAddition(ImageAdditionEvent $event)
    {
        if (preg_match(self::FILENAME_REGEX, $event->image->filename, $matches)) {
            $name = $matches["username"];
            $user = User::by_name($name);

            if (is_null($user)) {
                throw new UserDoesNotExist("Can't find any user named $name");
            }

            $event->image->owner_id = $user->id;
            $event->image->posted = $matches["date"];
            $event->image->filename = $matches["filename"];

            print_r($event->image);

            throw new UploadException("Testing filename regex metadata, sorry!");
        }
    }

    // public function onSetupBuilding(SetupBuildingEvent $event)
    // {
    //     $sb = $event->panel->create_new_block("EOKM Filter");

    //     $sb->start_table();
    //     $sb->add_text_option("eokm_username", "Username", true);
    //     $sb->add_text_option("eokm_password", "Password", true);
    //     $sb->end_table();
    // }
}
