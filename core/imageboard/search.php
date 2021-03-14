<?php declare(strict_types=1);
class Querylet
{
    public string $sql;
    public array $variables;

    public function __construct(string $sql, array $variables=[])
    {
        $this->sql = $sql;
        $this->variables = $variables;
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
    public string $tag;
    public bool $positive;

    public function __construct(string $tag, bool $positive)
    {
        $this->tag = $tag;
        $this->positive = $positive;
    }
}

class ImgCondition
{
    public Querylet $qlet;
    public bool $positive;

    public function __construct(Querylet $qlet, bool $positive)
    {
        $this->qlet = $qlet;
        $this->positive = $positive;
    }
}
