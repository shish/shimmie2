<?php

declare(strict_types=1);

namespace Shimmie2;

final class DatabaseConfig extends Config
{
    private string $cache_name;

    public function __construct(
        private Database $database
    ) {
        $this->cache_name = "config";
        $this->defaults = ConfigGroup::get_all_defaults();
        $this->values = cache_get_or_set($this->cache_name, fn () => $this->database->get_pairs(
            "SELECT name, value FROM config WHERE value IS NOT NULL"
        ));
    }

    protected function save(string $name): void
    {
        $this->database->execute("DELETE FROM config WHERE name = :name", ["name" => $name]);
        if (isset($this->values[$name])) {
            $this->database->execute(
                "INSERT INTO config (name, value) VALUES (:name, :value)",
                ["name" => $name, "value" => $this->values[$name]]
            );
        }

        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        Ctx::$cache->set($this->cache_name, $this->values);
        $this->database->notify("config");
    }
}
