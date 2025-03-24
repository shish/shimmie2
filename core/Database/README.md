# Database access in Shimmie

Shimmie sets the `$database` variable to a `Database` object, which has various useful methods to interact with an SQL database, eg:

- `get_all` (returns a 2D array of rows)
- `get_row` (returns a single row as an associative array)
- `get_col` (returns a single column as an array)
- `get_one` (returns a single value)
- `get_pairs` (returns an associative array of key-value pairs)

Also for creating tables there's a `create_table` method which makes supporting multiple database engines a _little_ easier.

Also note the `SCORE_AIPK` and `SCORE_INET` column types, which are database-specific aliases for auto-incrementing primary keys and IP addresses respectively, because every database has different syntax for these. These can be used like:

```php
<?php
$database->create_table("users", """
    id SCORE_AIPK,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    ip SCORE_INET,
""");
```

Then when `create_table` is called, it will automatically create the table with the appropriate syntax for the database engine being used.
