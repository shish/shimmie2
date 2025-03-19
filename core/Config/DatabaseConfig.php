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
    private string $cache_name;

    /**
     * @param array<string,string> $defaults
     */
    public function __construct(
        private Database $database,
        private string $table_name = "config",
        private ?string $sub_column = null,
        private ?string $sub_value = null,
        array $defaults = [],
    ) {
        global $cache;

        $this->cache_name = empty($sub_value) ? "config" : "config_{$sub_column}_{$sub_value}";
        $this->values = array_merge(
            $defaults,
            cache_get_or_set($this->cache_name, fn () => $this->get_values()),
        );
    }

    /**
     * @return array<string,string>
     */
    private function get_values(): array
    {
        $query = "SELECT name, value FROM {$this->table_name}";
        $args = [];

        if (!empty($this->sub_column) && !empty($this->sub_value)) {
            $query .= " WHERE {$this->sub_column} = :sub_value";
            $args["sub_value"] = $this->sub_value;
        }

        $values = [];
        // @phpstan-ignore-next-line
        foreach ($this->database->get_all($query, $args) as $row) {
            // versions prior to 2.12 would store null
            // instead of deleting the row
            if (!is_null($row["value"])) {
                $values[$row["name"]] = $row["value"];
            }
        }

        /** @var array<string,string> $values */
        return $values;
    }

    protected function save(string $name): void
    {
        global $cache;

        $query = "DELETE FROM {$this->table_name} WHERE name = :name";
        $args = ["name" => $name];
        $cols = ["name","value"];
        $params = [":name",":value"];
        if (!empty($this->sub_column) && !empty($this->sub_value)) {
            $query .= " AND $this->sub_column = :sub_value";
            $args["sub_value"] = $this->sub_value;
            $cols[] = $this->sub_column;
            $params[] = ":sub_value";
        }

        // @phpstan-ignore-next-line
        $this->database->execute($query, $args);

        if (isset($this->values[$name])) {
            $args["value"] = $this->values[$name];
            $this->database->execute(
                // @phpstan-ignore-next-line
                "INSERT INTO {$this->table_name} (".join(",", $cols).") VALUES (".join(",", $params).")",
                $args
            );
        }

        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        $cache->set($this->cache_name, $this->values);
        $this->database->notify($this->cache_name);
    }
}
