<?php

declare(strict_types=1);

namespace Shimmie2;

final class DatabaseUserConfig extends Config
{
    private string $cache_name;

    public function __construct(
        private Database $database,
        private int $user_id,
    ) {
        $this->cache_name = "user_config_{$user_id}";
        $this->metas = UserConfigGroup::get_all_metas();
        $this->values = cache_get_or_set($this->cache_name, function () {
            $values = [];
            foreach ($this->database->get_pairs(
                "SELECT name, value FROM user_config WHERE user_id = :user_id AND value IS NOT NULL",
                ["user_id" => $this->user_id]
            ) as $name => $value) {
                $values[$name] = isset($this->metas[$name])
                    ? $this->metas[$name]->type->fromString($value)
                    : $value;
            }
            return $values;
        });
    }

    protected function save(string $name): void
    {
        $this->database->execute(
            "DELETE FROM user_config WHERE user_id = :user_id AND name = :name",
            ["user_id" => $this->user_id, "name" => $name]
        );
        if (isset($this->values[$name])) {
            $this->database->execute(
                "INSERT INTO user_config (user_id, name, value) VALUES (:user_id, :name, :value)",
                ["user_id" => $this->user_id, "name" => $name, "value" => self::val2str($this->values[$name])]
            );
        }

        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        Ctx::$cache->set($this->cache_name, $this->values);
    }
}
