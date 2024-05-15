<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class QueryBuilderBase
{
    // ID to use for identifying auto-generated parameters
    protected string $id;
    public const CLASS_NAME = "Shimmie2\QueryBuilderBase";
    public function __construct()
    {
        $this->id = "QB" . uniqid();
    }

    public static function IsA(mixed $value): bool
    {
        return is_object($value) && is_a($value, QueryBuilderBase::CLASS_NAME);
    }

    protected function generateParameterId(string $suffix): string
    {
        return $this->id.$suffix."_";
    }

    abstract public function toSql(bool $omitOrders, bool $humanReadable): string;
    /**
     * @return mixed[]
     */
    abstract public function compileParameters(): array;
}

class QueryBuilder extends QueryBuilderBase
{
    /** @var string[] */
    private array $selectFields = [];
    private string|QueryBuilderBase $source;
    private string $sourceAlias;
    /** @var QueryBuilderJoin[] */
    public array $joins = [];
    public QueryBuilderCriteria $criteria;
    /** @var QueryBuilderOrder[] */
    public array $orders = [];
    /** @var QueryBuilderGroup[] */
    public array $groups = [];
    public int $limit = 0;
    public ?int $offset = 0;

    public const CLASS_NAME = "Shimmie2\QueryBuilder";
    public const LEFT_JOIN = "LEFT";
    public const RIGHT_JOIN = "RIGHT";
    public const INNER_JOIN = "INNER";
    public function __construct(string|QueryBuilder $source, string $sourceAlias = "")
    {
        parent::__construct();
        $this->criteria = new QueryBuilderCriteria();
        $this->source = $source;
        $this->sourceAlias = $sourceAlias;
    }

    public function crashIt(): void
    {
        $this->render(false, true)->crashIt();
    }

    public static function IsA(mixed $value): bool
    {
        return is_object($value) && is_a($value, QueryBuilder::CLASS_NAME);
    }

    public function addSelectField(string $name, string $alias = ""): void
    {
        $result = $name;
        if (!empty($alias)) {
            $result .= " $alias";
        }

        $this->selectFields[] = $result;
    }

    public function clearSelectFields(): void
    {
        $this->selectFields = [];
    }

    public function addJoin(string $type, string|QueryBuilder $source, string $sourceAlias = ""): QueryBuilderJoin
    {
        $join = new QueryBuilderJoin($type, $source, $sourceAlias);
        $this->joins[] = $join;
        return $join;
    }

    public function addOrder(string|QueryBuilderBase $source, bool $ascending = true): void
    {
        $order = new QueryBuilderOrder($source, $ascending);
        $this->orders[] = $order;
    }
    public function addQueryBuilderOrder(QueryBuilderOrder $order): void
    {
        $this->orders[] = $order;
    }
    public function clearOrder(): void
    {
        $this->orders = [];
    }


    public function addGroup(string $field): void
    {
        $order = new QueryBuilderGroup($field);
        $this->groups[] = $order;
    }

    public function addOrCriteria(): QueryBuilderCriteria
    {
        $output = new QueryBuilderCriteria("OR");
        $this->criteria->addQueryBuilderCriteria($output);
        return $output;
    }

    /**
     * @param mixed[] $parameters
     */
    public function addCriterion(string|QueryBuilderBase $left, string $comparison, string|QueryBuilderBase $right, array $parameters): void
    {
        $this->criteria->addCriterion($left, $comparison, $right, $parameters);
    }
    /**
     * @param mixed[] $options
     */
    public function addInCriterion(string|QueryBuilderBase $left, array $options): void
    {
        $this->criteria->addInCriterion($left, $options);
    }
    /**
     * @param mixed[] $parameters
     */
    public function addManualCriterion(string $statement, array $parameters = []): void
    {
        $this->criteria->addManualCriterion($statement, $parameters);
    }

    public function toSql(bool $omitOrders = false, bool $humanReadable = true): string
    {
        $output = "SELECT ";
        $output .= join(", ", $this->selectFields);
        if ($humanReadable) {
            $output .= "\r\n";
        }
        $output .= " FROM ";

        if (is_object($this->source) && is_a($this->source, self::CLASS_NAME)) {
            $output .= "(".$this->source->toSql($omitOrders, $humanReadable).")";
        } elseif (is_object($this->source) && is_a($this->source, QueryBuilderBase::CLASS_NAME)) {
            $output .= $this->source->toSql($omitOrders, $humanReadable);
        } else {
            $output .= " ".$this->source." ";
        }

        if (!empty($this->sourceAlias)) {
            $output .= " AS ".$this->sourceAlias." ";
        }

        if ($humanReadable) {
            $output .= "\r\n";
        }

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $output .= " ".$join->toSql($omitOrders, $humanReadable)." ";
                if ($humanReadable) {
                    $output .= "\r\n";
                }
            }
        }

        if (!$this->criteria->isEmpty()) {
            $output .= " WHERE ";
            $output .= $this->criteria->toSql($omitOrders, $humanReadable);
        }
        if ($humanReadable) {
            $output .= "\r\n";
        }

        if (!empty($this->groups)) {
            $output .= " GROUP BY ";
            foreach ($this->groups as $group) {
                $output .= $group->toSql($omitOrders, $humanReadable);
                $output .= ", ";
            }
            $output = substr($output, 0, strlen($output) - 2);
        }

        if (!$omitOrders && !empty($this->orders)) {
            $output .= " ORDER BY ";
            foreach ($this->orders as $order) {
                $output .= $order->toSql($omitOrders, $humanReadable);
                $output .= ", ";
            }
            $output = substr($output, 0, strlen($output) - 2);
        }

        if ($this->limit > 0) {
            $output .= " LIMIT ". $this->limit;
        }
        if ($this->offset > 0) {
            $output .= " OFFSET ". $this->offset;
        }


        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = [];

        if(!empty($this->joins)) {
            foreach($this->joins as $join) {
                $output = array_merge($output, $join->compileParameters());
            }
        }

        if (!$this->criteria->isEmpty()) {
            $output = array_merge($output, $this->criteria->compileParameters());
        }

        if (is_object($this->source) && is_a($this->source, QueryBuilderBase::CLASS_NAME)) {
            $output = array_merge($output, $this->source->compileParameters());
        }

        return $output;
    }

    public function render(bool $omitOrders = false, bool $humanReadable = true): RenderedQuery
    {
        $output = new RenderedQuery();
        $output->sql = $this->toSql($omitOrders, $humanReadable);
        $output->parameters = $this->compileParameters();
        return $output;
    }

    public function renderForCount(bool $humanReadable = true): RenderedQuery
    {
        $fieldsTemp = $this->selectFields;

        $this->selectFields = ["1"];

        $selectQuery = new QueryBuilder($this, "countSubquery");
        $selectQuery->addSelectField("COUNT(*)");

        $output = $selectQuery->render(true, $humanReadable);

        $this->selectFields = $fieldsTemp;
        return $output;
    }
}

class RenderedQuery
{
    public string $sql;
    /** @var mixed[] */
    public array $parameters = [];

    public function crashIt(): void
    {
        var_dump_format($this->parameters, "Parameters");
        var_dump_format($this->sql, "SQL");
        throw new SCoreException("SQL Query dump");
    }
}

class QueryBuilderCriteria extends QueryBuilderBase
{
    /** @var QueryBuilderBase[] */
    private array $criteria = [];
    private string $operator = "AND";

    public function __construct(string $operator = "AND")
    {
        parent::__construct();

        if ($operator !== "AND" && $operator !== "OR") {
            throw new SCoreException("operator must be \"AND\" or \"OR\"");
        }
        $this->operator = $operator;
    }

    public function isEmpty(): bool
    {
        return empty($this->criteria);
    }

    /**
     * @param mixed[] $parameters
     */
    public function addManualCriterion(string $statement, array $parameters): void
    {
        $this->criteria[] = new ManualQueryBuilderCriterion($statement, $parameters);
    }

    public function addQueryBuilderCriteria(QueryBuilderCriteria $criteria): void
    {
        $this->criteria[] = $criteria;
    }

    /**
     * @param mixed[] $parameters
     */
    public function addCriterion(string|QueryBuilderBase $left, string $comparison, string|QueryBuilderBase $right, array $parameters = []): void
    {
        $this->criteria[] = new QueryBuilderCriterion($left, $comparison, $right, $parameters);
    }
    /**
     * @param mixed[] $options
     */
    public function addInCriterion(string|QueryBuilderBase $left, array $options): void
    {
        $this->criteria[] = new QueryBuilderInCriterion($left, $options);
    }


    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        $output = "";
        if (empty($this->criteria)) {
            throw new SCoreException("No criterion set");
        }

        foreach ($this->criteria as $criterion) {
            $output .= $criterion->toSql($omitOrders, $humanReadable);
            $output .= " ".$this->operator." ";
        }
        $output = substr($output, 0, strlen($output) - strlen($this->operator) - 2);

        if (sizeof($this->criteria) > 1) {
            $output = " ($output) ";
        } else {
            $output = " $output ";
        }
        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = [];

        if (empty($this->criteria)) {
            throw new SCoreException("No criterion set");
        }

        foreach ($this->criteria as $criteria) {
            $output = array_merge($output, $criteria->compileParameters());
        }

        return $output;
    }
}

class QueryBuilderCriterion extends QueryBuilderBase
{
    private string|QueryBuilderBase $left;
    private string|QueryBuilderBase $right;
    private string $comparison;
    /** @var mixed[] */
    private array $parameters = [];

    /**
     * @param mixed[] $parameters
     */
    public function __construct(string|QueryBuilderBase $left, string $comparison, string|QueryBuilderBase $right, array $parameters)
    {
        parent::__construct();

        $this->left = $left;
        $this->comparison = $comparison;
        $this->right = $right;
        $this->parameters = $parameters;
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        if (is_object($this->left) && is_a($this->left, QueryBuilderBase::CLASS_NAME)) {
            $output = "(".$this->left->toSql($omitOrders, $humanReadable).")";
        } else {
            $output = $this->left;
        }

        $output .= " ".$this->comparison." ";

        if (is_object($this->right) && is_a($this->right, QueryBuilderBase::CLASS_NAME)) {
            $output .= "(" . $this->right->toSql($omitOrders, $humanReadable) . ")";
        } else {
            $output .= $this->right;
        }
        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = $this->parameters;
        if (is_object($this->left) && is_a($this->left, QueryBuilderBase::CLASS_NAME)) {
            $output = array_merge($output, $this->left->compileParameters());
        }
        if (is_object($this->right) && is_a($this->right, QueryBuilderBase::CLASS_NAME)) {
            $output = array_merge($output, $this->right->compileParameters());
        }

        return $output;
    }
}


class QueryBuilderInCriterion extends QueryBuilderBase
{
    private string|QueryBuilderBase $left;
    /** @var mixed[] */
    private array $options = [];

    /**
     * @param mixed[] $options
     */
    public function __construct(string|QueryBuilderBase $left, array $options)
    {
        parent::__construct();

        $this->id = "QB".uniqid();
        $this->left = $left;
        $this->options = $options;
        if (empty($options)) {
            throw new SCoreException("Options cannot be empty");
        }
    }

    private function isSafe(mixed $value): bool
    {
        if (is_string($value)) {
            return false;
        }

        if (is_int($value)) {
            return true;
        }

        // TODO: Other data types

        return false;
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        if (is_object($this->left) && is_a($this->left, QueryBuilderBase::CLASS_NAME)) {
            $output = "(".$this->left->toSql($omitOrders, $humanReadable).")";
        } else {
            $output = $this->left;
        }

        $output .= " IN (";

        for ($i = 0;$i < sizeof($this->options);$i++) {
            $value = $this->options[$i];

            if ($this->isSafe($value)) {
                $output .= " ".$value." ";
            } else {
                $id = $this->generateParameterId(strval($i));
                $output .= " :".$id." ";
            }
            $output .= ", ";
        }
        $output = substr($output, 0, strlen($output) - 2);
        $output .= ") ";

        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = [];

        for ($i = 0;$i < sizeof($this->options);$i++) {
            $value = $this->options[$i];

            if (!$this->isSafe($value)) {
                $id = $this->generateParameterId(strval($i));
                $output[$id] = $this->options[$i];
            }
        }

        return $output;
    }
}


class ManualQueryBuilderCriterion extends QueryBuilderBase
{
    private string $statement;
    /** @var mixed[] */
    private array $parameters = [];

    /**
     * @param mixed[] $parameters
     */
    public function __construct(string $statement, array $parameters)
    {
        parent::__construct();

        $this->statement = $statement;
        $this->parameters = $parameters;
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        return $this->statement;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        return $this->parameters;
    }
}

class QueryBuilderOrder extends QueryBuilderBase
{
    public ?QueryBuilderBase $sourceBuilder = null;
    public string $sourceString;
    private ?bool $ascending;
    /** @var mixed[] */
    private array $parameters = [];
    public const CLASS_NAME = "Shimmie2\QueryBuilderOrder";
    public function __construct(string|QueryBuilderBase $source, ?bool $ascending)
    {
        parent::__construct();
        if (is_string($source)) {
            if(empty($source)) {
                throw new SCoreException("Source parameter cannot be empty");
            }
            $this->sourceString = $source;
        } else {
            $this->sourceBuilder = $source;
        }
        $this->ascending = $ascending;
    }
    public static function IsA(mixed $value): bool
    {
        return is_object($value) && is_a($value, QueryBuilderOrder::CLASS_NAME);
    }

    public function isSourceString(): bool
    {
        return is_null($this->sourceBuilder);
    }
    public function getSourceString(): string
    {
        if(!$this->isSourceString()) {
            return "";
        }
        return $this->sourceString;
    }
    public function getAscending(): bool
    {
        return $this->ascending;
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        if($this->isSourceString()) {
            $output = $this->sourceString;
        } else {
            $output = "(".$this->sourceBuilder->toSql($omitOrders, $humanReadable).")";
        }

        if($this->ascending === true) {
            $output .= " ASC ";
        } elseif($this->ascending === false) {
            $output .= " DESC ";
        }
        return $output;
    }

    /**
     * @return QueryBuilderOrder[]
     */
    public static function parse(string $input): array
    {
        $output = [];
        if(str_contains($input, "(") ||
            str_contains($input, ")")) {
            // This means some complex function is going on, just use it as-is
            $slices = [$input];
        } else {
            $slices = explode(",", trim($input));
        }
        foreach($slices as $slice) {
            $slice = trim($slice);
            $ascending = true;
            if(str_ends_with(strtolower($slice), " desc")) {
                $ascending = false;
                $slice = substr($slice, 0, strlen($slice) - 5);
            } elseif(str_ends_with(strtolower($slice), " asc")) {
                $slice = substr($slice, 0, strlen($slice) - 4);
            }
            $output[] = new QueryBuilderOrder($slice, $ascending);
        }
        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = $this->parameters;
        if (!$this->isSourceString()) {
            $output = array_merge($output, $this->sourceBuilder->compileParameters());
        }
        return $output;
    }
}

class QueryBuilderGroup extends QueryBuilderBase
{
    private string|QueryBuilderBase $field;
    /** @var mixed[] */
    private array $parameters = [];

    public function __construct(string|QueryBuilderBase $field)
    {
        parent::__construct();
        $this->field = $field;
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        if (is_object($this->field) && is_a($this->field, QueryBuilderBase::CLASS_NAME)) {
            $output = "(".$this->field->toSql($omitOrders, $humanReadable).")";
        } else {
            $output = $this->field;
        }

        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = $this->parameters;
        if (is_object($this->field) && is_a($this->field, QueryBuilderBase::CLASS_NAME)) {
            $output = array_merge($output, $this->field->compileParameters());
        }
        return $output;
    }
}


class QueryBuilderJoin extends QueryBuilderBase
{
    private string|QueryBuilder $source;
    private string $sourceAlias;
    private QueryBuilderCriteria $criteria;
    private string $type;

    public function __construct(string $type, string|QueryBuilder $source, string $sourceAlias = "")
    {
        parent::__construct();

        $this->criteria = new QueryBuilderCriteria();

        $this->source = $source;
        $this->sourceAlias = $sourceAlias;

        if ($type !== QueryBuilder::INNER_JOIN && $type !== QueryBuilder::LEFT_JOIN && $type !== "RIGHT" && $type !== "LEFT OUTER" && $type !== "RIGHT OUTER") {
            throw new SCoreException("Join type \"$type\" not recognized");
        }
        $this->type = $type;
    }

    public function addOrCriteria(): QueryBuilderCriteria
    {
        $output = new QueryBuilderCriteria("OR");
        $this->criteria->addQueryBuilderCriteria($output);
        return $output;
    }

    /**
     * @param mixed[] $parameters
     */
    public function addCriterion(string|QueryBuilderBase $left, string $comparison, string|QueryBuilderBase $right, array $parameters = []): void
    {
        $this->criteria->addCriterion($left, $comparison, $right, $parameters);
    }
    /**
     * @param mixed[] $parameters
     */
    public function addManualCriterion(string $statement, array $parameters = []): void
    {
        $this->criteria->addManualCriterion($statement, $parameters);
    }
    /**
     * @param mixed[] $options
     */
    public function addInCriterion(string|QueryBuilderBase $left, array $options): void
    {
        $this->criteria->addInCriterion($left, $options);
    }

    public function toSql(bool $omitOrders, bool $humanReadable): string
    {
        $output = " ".$this->type." JOIN ";

        if (is_object($this->source) && is_a($this->source, QueryBuilderBase::CLASS_NAME)) {
            $output .= "(".$this->source->toSql($omitOrders, $humanReadable).")";
        } else {
            $output .= $this->source;
        }
        if (!empty($this->sourceAlias)) {
            $output .= " ".$this->sourceAlias." ";
        }

        $output .= " ON ". $this->criteria->toSql($omitOrders, $humanReadable);

        return $output;
    }

    /**
     * @return mixed[]
     */
    public function compileParameters(): array
    {
        $output = $this->criteria->compileParameters();
        if (is_object($this->source) && is_a($this->source, QueryBuilderBase::CLASS_NAME)) {
            $output = array_merge($output, $this->source->compileParameters());
        }
        return $output;
    }
}
