<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Loads the config list from a table in a given database, the table should
 * be called config and have the schema:
 *
 * \code
 *  CREATE TABLE user_config(
 *      user_id INT NOT NULL,
 *      name VARCHAR(255) NOT NULL,
 *      value TEXT
 *  );
 * \endcode
 */
final class DatabaseUserConfig extends Config
{
    private string $cache_name;

    public function __construct(
        private Database $database,
        private int $user_id,
    ) {
        $this->cache_name = "user_config_{$user_id}";
        $defaults = UserConfigGroup::get_all_defaults();
        $values = cache_get_or_set($this->cache_name, fn () => $this->database->get_pairs(
            "SELECT name, value FROM user_config WHERE user_id = :user_id AND value IS NOT NULL",
            ["user_id" => $this->user_id]
        ));
        $this->values = array_merge($defaults, $values);
    }

    protected function save(string $name): void
    {
        $this->database->execute(
            "DELETE FROM user_config WHERE name = :name AND user_id = :user_id",
            ["name" => $name, "user_id" => $this->user_id]
        );

        if (isset($this->values[$name])) {
            $this->database->execute(
                "INSERT INTO user_config (user_id, name, value) VALUES (:user_id, :name, :value)",
                ["user_id" => $this->user_id, "name" => $name, "value" => $this->values[$name]]
            );
        }

        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        Ctx::$cache->set($this->cache_name, $this->values);
    }
}
