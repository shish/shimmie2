<?php
class Querylet
{
    /** @var string */
    public $sql;
    /** @var array */
    public $variables;

    public function __construct(string $sql, array $variables=[])
    {
        $this->sql = $sql;
        $this->variables = $variables;
    }

    public function append(Querylet $querylet)
    {
        $this->sql .= $querylet->sql;
        $this->variables = array_merge($this->variables, $querylet->variables);
    }

    public function append_sql(string $sql)
    {
        $this->sql .= $sql;
    }

    public function add_variable($var)
    {
        $this->variables[] = $var;
    }
}

class TagQuerylet
{
    /** @var string  */
    public $tag;
    /** @var bool  */
    public $positive;

    public function __construct(string $tag, bool $positive)
    {
        $this->tag = $tag;
        $this->positive = $positive;
    }
}

class ImgQuerylet
{
    /** @var \Querylet */
    public $qlet;
    /** @var bool */
    public $positive;

    public function __construct(Querylet $qlet, bool $positive)
    {
        $this->qlet = $qlet;
        $this->positive = $positive;
    }
}
