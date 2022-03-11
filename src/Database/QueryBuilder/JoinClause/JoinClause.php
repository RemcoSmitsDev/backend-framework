<?php

declare(strict_types=1);

namespace Framework\Database\QueryBuilder\JoinClause;

use Framework\Database\QueryBuilder\QueryBuilder;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class JoinClause extends QueryBuilder
{
    /**
     * type of join query ( INNER | LEFT JOIN | RIGHT JOIN | CROSS JOIN).
     *
     * @var string
     */
    public string $type;

    /**
     * table of join table.
     *
     * @var string
     */
    public string $table;

    /**
     * reference of group/parent query.
     *
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * function __construct.
     *
     * @param QueryBuilder $query
     * @param string       $table
     * @param string       $type
     */
    public function __construct(QueryBuilder $query, string $table, string $type)
    {
        $this->query = $query;
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * function on.
     *
     * @param string $first
     * @param string $operator
     * @param string $value
     * @param string $boolean
     *
     * @return JoinClause
     */
    public function on(string $first, string $operator, string $value, string $boolean = 'AND'): self
    {
        // add where statement and return self
        return $this->whereColumn($first, $operator, $value, $boolean);
    }

    /**
     * function orOn.
     *
     * @param string $first
     * @param string $operator
     * @param string $value
     *
     * @return JoinClause
     */
    public function orOn(string $first, string $operator, string $value): self
    {
        return $this->on($first, $operator, $value, 'OR');
    }
}
