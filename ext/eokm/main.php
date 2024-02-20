<?php

declare(strict_types=1);

namespace Shimmie2;

class Eokm extends Extension
{
    public function get_priority(): int
    {
        return 40;
    } // early, to veto ImageUploadEvent

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $config;
        $username = $config->get_string("eokm_username");
        $password = $config->get_string("eokm_password");

        if ($username && $password) {
            $ch = \Safe\curl_init("https://api.eokmhashdb.nl/v1/check/md5");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', $additionalHeaders));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $event->image->hash);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $return = curl_exec($ch);
            curl_close($ch);

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if ($return == "false") {
                // all ok
            } elseif ($return == "true") {
                log_warning("eokm", "User tried to upload banned image {$event->image->hash}");
                throw new UploadException("Post banned");
            } else {
                log_warning("eokm", "Unexpected return from EOKM: $return");
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("EOKM Filter");

        $sb->start_table();
        $sb->add_text_option("eokm_username", "Username", true);
        $sb->add_text_option("eokm_password", "Password", true);
        $sb->end_table();
    }
}
