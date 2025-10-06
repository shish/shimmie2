<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroOTLP\Client;

final class OTLPCommon extends Extension
{
    public const KEY = "otlp_common";

    public static Client $client;

    public function onInitExt(InitExtEvent $event): void
    {
        $host = Ctx::$config->get(OTLPCommonConfig::HOST);
        static::$client = new Client(
            $host,
            resourceAttributes: [
                'service.name' => 'shimmie2',
                'service.instance.id' => gethostname() ?: 'unknown',
            ],
            scopeAttributes: [
                'name' => 'shimmie2',
                'version' => SysConfig::getVersion(),
            ],
        );

        $event->add_shutdown_handler(function () {
            static::$client->flush();
        });
    }

    // Near the end so that we flush after other OTLP extensions
    // have had a chance to log things.
    public function get_priority(): int
    {
        return 95;
    }
}
