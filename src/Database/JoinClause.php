<?php

namespace Framework\Database;

class JoinClause extends Database
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
     * @var Database
     */

    protected Database $query;

    /**
     * function __construct
     * @param Database $query
     * @param string $table
     * @param string $type
     */

    public function __construct(Database $query, string $table, string $type)
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
     * @return Database
     */

    public function on(string $first, string $operator, string $value, string $boolean = 'AND'): self
    {
        // add where statement and return self
        return $this->whereColumn($first, $operator, $value, $boolean);
    }

    /**
     * functio orOn
     * @param string $first
     * @param string $operator
     * @param string $value
     * @return Database
     */

    public function orOn(string $first, string $operator, string $value): self
    {
        return $this->on($first, $operator, $value, 'OR');
    }
}