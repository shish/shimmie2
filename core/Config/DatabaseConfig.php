<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Loads the config list from a table in a given database, the table should
 * be called config and have the schema:
 *
 * \code
 *  CREATE TABLE config(
 *      name VARCHAR(255) NOT NULL,
 *      value TEXT
 *  );
 * \endcode
 */
final class DatabaseConfig extends Config
{
    public function __construct(
        private Database $database
    ) {
        $defaults = ConfigGroup::get_all_defaults();
        $values = cache_get_or_set("config", fn () => $this->database->get_pairs(
            "SELECT name, value FROM config WHERE value IS NOT NULL"
        ));
        $this->values = array_merge($defaults, $values);
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
        Ctx::$cache->set("config", $this->values);
        $this->database->notify("config");
    }
}
