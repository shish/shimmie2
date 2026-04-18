<?php

declare(strict_types=1);

namespace Shimmie2;

final class Ctx
{
    public static \Psr\SimpleCache\CacheInterface $cache;
    public static Config $config;
    public static Database $database;
    public static EventBus $event_bus;
    public static Page $page;
    public static \MicroOTLP\SpanBuilder $root_span;
    public static \MicroOTLP\Client $tracer;
    public static User $user;
}
