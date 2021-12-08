<?php

namespace Framework\Database\JoinClause;

use Framework\Database\QueryBuilder\QueryBuilder;

class JoinClause extends QueryBuilder
{
    /**
     * type of join query ( INNER | LEFT JOIN | RIGHT JOIN | CROSS JOIN)
     * @var string
     */

    public string $type;

    /**
     * table of join table
     * @var string
     */

    public string $table;

    /**
     * reference of group/parent query
     * @var QueryBuilder
     */

    protected QueryBuilder $query;

    /**
     * function __construct
     * @param QueryBuilder $query
     * @param string $table
     * @param string $type
     */

    public function __construct(QueryBuilder $query, string $table, string $type)
    {
        $this->query = $query;
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * function on
     * @param string $first
     * @param string $operator
     * @param string $value
     * @param string $boolean
     * @return JoinClause
     */

    public function on(string $first, string $operator, string $value, string $boolean = 'AND'): self
    {
        // add where statement and return self
        return $this->whereColumn($first, $operator, $value, $boolean);
    }

    /**
     * function orOn
     * @param string $first
     * @param string $operator
     * @param string $value
     * @return JoinClause
     */

    public function orOn(string $first, string $operator, string $value): self
    {
        return $this->on($first, $operator, $value, 'OR');
    }
}