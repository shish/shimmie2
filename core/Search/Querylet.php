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
 *
 * @phpstan-import-type QueryParams from Database
 */
final class Querylet
{
    private static int $counter = 0;
    /** @var literal-string */
    public string $sql;
    /** @var QueryParams */
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
     * @param QueryParams $variables
     */
    public function __construct(
        string $sql,
        array $variables = [],
    ) {
        self::$counter++;
        $id = self::$counter;  // Local variable, not instance variable

        // Namespace variables by appending the ID to each parameter name
        // Sort by key length (descending) to process longer keys first and avoid partial matches
        // (e.g., :val1 in :val12 would incorrectly match without this ordering)
        $keys = array_keys($variables);
        $sortedKeys = $keys;
        usort($sortedKeys, fn ($a, $b) => strlen($b) - strlen($a));

        // Process replacements in length-descending order
        foreach ($sortedKeys as $key) {
            $pattern = '/:' . preg_quote($key, '/') . '\b/';
            $newKey = $key . $id;
            $sql = \Safe\preg_replace($pattern, ':' . $newKey, $sql);
        }

        // Build newVariables in original key order to preserve insertion order
        $newVariables = [];
        foreach ($keys as $key) {
            $newKey = $key . $id;
            $newVariables[$newKey] = $variables[$key];
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
