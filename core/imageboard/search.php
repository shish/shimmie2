<?php

declare(strict_types=1);

namespace Shimmie2;

class Querylet
{
    public function __construct(
        public string $sql,
        public array $variables=[],
    ) {
    }

    public function append(Querylet $querylet): void
    {
        $this->sql .= $querylet->sql;
        $this->variables = array_merge($this->variables, $querylet->variables);
    }

    public function append_sql(string $sql): void
    {
        $this->sql .= $sql;
    }

    public function add_variable($var): void
    {
        $this->variables[] = $var;
    }
}

class TagCondition
{
    public function __construct(
        public string $tag,
        public bool $positive,
    ) {
    }
}

class ImgCondition
{
    public function __construct(
        public Querylet $qlet,
        public bool $positive,
    ) {
    }
}
