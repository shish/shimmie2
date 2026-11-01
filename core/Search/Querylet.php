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
    private static int $counter = 0;
    /** @var literal-string */
    public string $sql;
    /** @var sql-params-array */
    public array $variables;

    /**
     * Reset the counter (primarily for testing)
     */
    public static function resetCounter(): void
    {
        self::$counter = 0;
    }

    /**
     * @param literal-string $sql
     * @param sql-params-array $variables
     */
    public function __construct(
        string $sql,
        array $variables = [],
    ) {
        self::$counter++;
        $id = self::$counter;  // Local variable, not instance variable

        // Namespace variables by appending the ID to each parameter name
        $newVariables = [];
        foreach ($variables as $key => $value) {
            $newKey = $key . $id;
            $newVariables[$newKey] = $value;
            // Replace :key with :keyN in the SQL string
            $sql = str_replace(':' . $key, ':' . $newKey, $sql);
        }

        // be very careful here - we take a literal string as input,
        // and add namespaces to the parameter names but otherwise
        // leave it as-is
        // @phpstan-ignore-next-line
        $this->sql = $sql;
        $this->variables = $newVariables;
    }

    public function append(Querylet $querylet): void
    {
        $this->sql .= $querylet->sql;
        $this->variables = array_merge($this->variables, $querylet->variables);
    }
}
