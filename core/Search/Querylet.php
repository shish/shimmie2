<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A small chunk of SQL code + parameters, to be used in a larger query
 *
 * eg
 *
 * $q = new Querylet("SELECT * FROM images");
 * $q->append(new Querylet(" WHERE id = :id", ["id" => 123]));
 * $q->append(new Querylet(" AND rating = :rating", ["rating" => "safe"]));
 * $q->append(new Querylet(" ORDER BY id DESC"));
 *
 * becomes
 *
 * SELECT * FROM images WHERE id = :id AND rating = :rating ORDER BY id DESC
 * ["id" => 123, "rating" => "safe"]
 */
final class Querylet
{
    /**
     * @param string $sql
     * @param sql-params-array $variables
     */
    public function __construct(
        public string $sql,
        public array $variables = [],
    ) {
    }

    public function append(Querylet $querylet): void
    {
        $this->sql .= $querylet->sql;
        $this->variables = array_merge($this->variables, $querylet->variables);
    }
}
