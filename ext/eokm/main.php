<?php

declare(strict_types=1);

namespace Shimmie2;

final class Eokm extends Extension
{
    public const KEY = "eokm";

    #[EventListener(priority: 40)] // early, to veto ImageUploadEvent
    public function onImageAddition(ImageAdditionEvent $event): void
    {
        $username = Ctx::$config->get(EokmConfig::USERNAME);
        $password = Ctx::$config->get(EokmConfig::PASSWORD);

        if ($username && $password) {
            $ch = \Safe\curl_init("https://api.eokmhashdb.nl/v1/check/md5");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', $additionalHeaders));
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $event->image->hash);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $return = curl_exec($ch);
            curl_close($ch);

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if ($return === "false") {
                // all ok
            } elseif ($return === "true") {
                Log::warning("eokm", "User tried to upload banned image {$event->image->hash}");
                throw new UploadException("Post banned");
            } else {
                Log::warning("eokm", "Unexpected return from EOKM: $return");
            }
        }
    }
}
