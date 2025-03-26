<?php

declare(strict_types=1);

namespace Shimmie2;

use Psr\SimpleCache\CacheInterface;

final class Ctx
{
    public static CacheInterface $cache;
    public static Config $config;
    public static Database $database;
    public static EventBus $event_bus;
    public static Page $page;
    public static \EventTracer $tracer;
    public static User $user;
    public static bool $tracer_enabled = false;

    public static function setCache(CacheInterface $_cache): CacheInterface
    {
        self::$cache = $_cache;
        return $_cache;
    }

    public static function setConfig(Config $_config): Config
    {
        global $config;
        $config = $_config;
        self::$config = $_config;
        return $_config;
    }

    public static function setUser(User $_user): User
    {
        global $user;
        $user = $_user;
        self::$user = $_user;
        return $_user;
    }

    public static function setDatabase(Database $_database): Database
    {
        global $database;
        $database = $_database;
        self::$database = $_database;
        return $_database;
    }

    public static function setEventBus(EventBus $_event_bus): EventBus
    {
        self::$event_bus = $_event_bus;
        return $_event_bus;
    }

    public static function setPage(Page $_page): Page
    {
        global $page;
        $page = $_page;
        self::$page = $_page;
        return $_page;
    }

    public static function setTracer(\EventTracer $tracer): \EventTracer
    {
        self::$tracer = $tracer;
        return $tracer;
    }
}
