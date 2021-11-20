<?php

namespace Framework\Database;

use Framework\Database\Connection\Connection;
use Framework\Database\DatabaseHelpers;
use Framework\Database\JoinClause;

class Database extends QueryBuilder
{
    use DatabaseHelpers;

    /**
     * keeps track of show query's
     * @var bool
     */

    private bool $logSql = false;

    /**
     * keep track of connection
     * @var Connection|\PDO|null
     */

    private Connection | \PDO | null $connection;

    /**
     * fetch mode
     * @var int
     */

    protected int $fetchMode = \PDO::FETCH_OBJ;

    /**
     * valid query types
     * @var array
     */

    private array $validTypes = [
        'insert',
        'update',
        'select',
        'delete',
        'truncate',
        'drop',
    ];

    /**
     * builder parts for making database query
     * @var array
     */

    protected array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'limit' => [],
        'groupBy' => [],
        'orderBy' => [],
    ];

    /**
     * keeps track of select columns
     * @var array
     */

    protected array $columns = [];

    /**
     * keep track of main table name
     * @var string
     */

    protected string $from = '';

    /**
     * keeps track of joins
     * @var array
     */

    protected array $joins = [];

    /**
     * keeps track of where statements
     * @var array
     */

    protected array $wheres = [];

    /**
     * keeps track of limit amount
     * @var int
     */

    protected int $limit;

    /**
     * keeps track of all group statements
     * @var array
     */

    protected array $groups = [];

    /**
     * keeps track of all order statements
     * @var array
     */

    protected array $orders = [];

    /**
     * keep track if there went something wrong
     * @var bool
     */
    protected bool $errorWhileExecuting = false;

    /**
     * keep track of already executed query
     * @var bool
     */
    private bool $hasBeenExecuted = false;

    /**
     * keep track of statement
     */
    private $statement;

    /**
     * function __construct
     * @param Connection|\PDO $connection
     */

    public function __construct(Connection | \PDO $connection = null)
    {
        // make connection to database
        $this->connection = $connection ?: new Connection();

        // try to start connection
        if ($this->connection instanceof Connection) {
            $this->connection = $this->connection->start();
        }
    }

    /**
     * function logSql
     */
    public function logSql(): self
    {
        // set logSql to true
        $this->logSql = true;

        // return self
        return $this;
    }

    /**
     * select table with option for select columns
     * @param string       $tableName
     * @param string|array $select
     * @return self
     */

    public function table(string $tableName, string | array $select = '*'): self
    {
        // add tablename
        $this->from = $tableName;

        // make select statement 
        // and return self
        return $this->select($select);
    }

    /**
     * Select columns from table
     * @param string|array $select
     * @return self
     */

    public function select(string | array $select = ['*']): self
    {
        // make select
        $columns = (array) $select;

        // loop trough all columns
        foreach ($columns as $as => $column) {
            // check if is subSelect with as name
            if (is_string($as) && $column instanceof \Closure) {
                // make subSelect
                $this->subSelect($column, $as);
            }
            // when column is an array
            elseif (is_array($column)) {
                $this->columns = array_merge($this->columns, $this->selectFormat($column));
            }
            // else is string
            else {
                // trim spaces
                $column = trim($column);

                // check if * is in column string
                if (preg_match('/\*|count\(.+?\)|DISTINCT/i', $column)) {
                    $this->columns[] = "$column";
                } else {
                    $this->columns[] = "`$column`";
                }
            }
        }

        // make select columns unique
        $this->columns = array_unique($this->columns);

        // return self
        return $this;
    }

    /**
     * function subSelect
     * @param string|\Closure $query
     * @param string          $as
     * @return void
     */

    public function subSelect(string | \Closure $query, string $as): void
    {
        // get bindings from query
        [$query, $bindData] = $this->createSubSelect($query, $as);

        // add binddata
        // $this->bindings['select'] = array_merge($this->bindings['select'], $bindData);

        // format subSelect
        $this->columns[] = "({$query}) as {$as}";
    }

    //
    // WHERE functions
    //

    /**
     * function where
     * @param $column
     * @param array|string $operator
     * @param $value
     * @param string $boolean
     * @return self
     */

    public function where($column, array | string $operator = null, $value = null, string $boolean = 'AND'): self
    {
        // check is instanceof \Closure
        if ($column instanceof \Closure && is_null($value)) {
            // return self and handle whereClosure
            return $this->whereClosure($column, $boolean);
        }

        // when value is null
        if (is_null($value) && !is_null($operator)) {
            // make operator the value
            $value = $operator;
            // reset operator to '='
            $operator = '=';
        }

        // make array of columns/values
        $columns = (array) $column;
        $values = (array) $value;

        // loop trough all columns
        foreach ($columns as $key => $column) {
            // get value by column
            $value = $values[$key] ?? null;
            // check if operator is an array
            if (is_array($operator)) {
                $_operator = $operator[$key] ?? '=';
            }

            // force to be real int
            if (is_int($value)) {
                $value = (int) $value;
            }

            // add to where statement
            $this->wheres[] = [
                'column' => $column,
                'operator' => $_operator ?? $operator,
                'value' => $value,
                'boolean' => $boolean,
            ];

            // add value binding
            $this->bindings['where'][] = $value;
        }

        // return self
        return $this;
    }

    /**
     * function whereRaw
     * @param string|\Closure $query
     * @param mixed $bindData
     * @param string $boolean
     * @param self
     */

    public function whereRaw(string|\Closure $query, $bindData = [], string $boolean = 'AND')
    {
        // check if query is string
        if (is_string($query)) {
            // add to where statement
            $this->wheres[] = [
                'type' => 'raw',
                'column' => '',
                'operator' => '',
                'value' => $query,
                'boolean' => $boolean,
            ];

            // add binddata to builder parts
            $this->bindings['where'] = array_merge($this->bindings['where'], $this->flattenArray((array) $bindData));

            // return self
            return $this;
        }

        // get query
        $query($query = new static($this->connection));

        // get formatted where statement with bindData
        [$whereClause, $bindData] = $query->formatWhere($query->wheres);

        // add to where statement
        $this->wheres[] = [
            'type' => 'raw',
            'column' => '',
            'operator' => '',
            'value' => '(' . trim($whereClause) . ')',
            'boolean' => $boolean,
        ];

        // add binddata to builder parts
        $this->bindings['where'] = array_merge($this->bindings['where'], $this->flattenArray($query->bindings['where']));

        // return self
        return $this;
    }

    /**
     * function orWhere
     * @param mixed $column   Column names from tables
     * @param string $operator
     * @param mixed $value
     * @return self
     */

    public function orWhere($column, string $operator = null, $value = null)
    {
        // return self and make where statement with OR
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * function whereIn
     * @param string|\Closure $column
     * @param array $values
     * @param string $boolean
     * @return self
     */

    public function whereIn(string | \Closure $column, array $values = null, string $boolean = 'AND'): self
    {
        // check if $column is instance of closure that means that whereIn will be an subWhere
        // the where statement will have ( ) wrapped around it
        if ($column instanceof \Closure) {
            // call closure
            $column($query = new static($this->connection));
            // get bindings from query
            [$query,] = $this->createSubSelect($query);

            // add to where statement
            $this->wheres[] = [
                'type' => 'raw',
                'column' => 'id',
                'operator' => 'IN',
                'value' => '(' . $query . ')',
                'boolean' => $boolean,
            ];

            // return self
            return $this;
        }

        // add to where statement
        $this->wheres[] = [
            'type' => 'raw',
            'column' => $column,
            'operator' => 'IN',
            'value' => '(?)',
            'boolean' => $boolean,
        ];

        // add values to where bindings
        $this->bindings['where'][] = implode(',', $values);

        // return self
        return $this;
    }

    /**
     * function whereColumn
     * @param string $column
     * @param string|null $operator
     * @param string|null $value
     * @param string $boolean
     */

    public function whereColumn(string $column, string $operator = null, string $value, string $boolean = 'AND')
    {
        // add to where statement
        $this->wheres[] = [
            'type' => 'column',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        // return self
        return $this;
    }

    // 
    // Join methods
    // 

    public function join(string $table, string|\Closure $first, string $operator = null, string $value = null, string $type = 'INNER'): self
    {
        // make instance of 
        $join = new JoinClause($this, $table, $type);

        // check if first is instance of closure
        if ($first instanceof \Closure) {
            // make closure and make instance of JoinClause
            $first($join);
            // add join query
            $this->joins[] = $join;
        } else {
            $this->joins[] = $join->on($first, $operator, $value);
        }

        // loop trough all bindings
        foreach ($join->bindings as $key => $binding) {
            // merge binding
            $this->bindings[$key] = array_merge($this->bindings[$key], $binding);
        }

        // return self
        return $this;
    }

    /**
     * function leftJoin
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $value
     */

    public function leftJoin(string $table, string|\Closure $first, string $operator = null, string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'left');
    }

    /**
     * function rightJoin
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $value
     */

    public function rightJoin(string $table, string|\Closure $first, string $operator = null, string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'right');
    }

    //
    // Fetch methods
    //

    /**
     * function all
     * @param mixed $fallbackReturnType
     * @param int $fetchMode
     */

    public function all($fallbackReturnType = false, int $fetchMode = null)
    {
        // return all results
        $returnValue = $this->handleExecution(...$this->selectToSql($this))->fetchAll($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    /**
     * function one
     * @param mixed $fallbackReturnType
     * @param int $fetchMode
     */

    public function one($fallbackReturnType = false, int $fetchMode = null)
    {
        // make sure that limit is 1
        $this->limit(1);

        // return one result
        $returnValue = $this->handleExecution(...$this->selectToSql($this))->fetch($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    /**
     * function column
     * @param mixed $fallbackReturnType
     * @param int $fetchMode
     */

    public function column($fallbackReturnType = false, int $fetchMode = null)
    {
        // get return value
        $returnValue = $this->handleExecution(...$this->selectToSql($this))->fetchColumn($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    //
    // action methods
    //

    /**
     * function insert
     * @param array $insert
     * @return bool|int
     */

    public function insert(array $insertData): bool|int
    {
        // check if there exists an table
        if (empty($this->from)) {
            return false;
        }

        // return insert id(s) or false when execution was failed
        return $this->handleExecution(...$this->insertToSql($this, $insertData));
    }

    /** 
     * function update
     * @param array $updateData
     * @return bool
     */
    public function update(array $updateData): bool
    {
        // check if update data is empty
        if (empty($updateData && $this->from)) {
            return false;
        }

        // return true or false (based on status of execution)
        return $this->handleExecution(...$this->updateToSql($this, $updateData));
    }

    /** 
     * function update
     * @return bool
     */
    public function delete(): bool
    {
        // check if update data is empty
        if (empty($this->from)) {
            return false;
        }

        // return true or false (based on status of execution)
        return $this->handleExecution(...$this->deleteToSql($this));
    }


    /**
     * function raw
     * @param string $query
     * @param array $bindData
     * @return mixed
     */

    public function raw(string $query, $bindData = []): mixed
    {
        // handle execution of query
        $response = $this->handleExecution(
            $query,
            $this->flattenArray((array) $bindData),
            $type
        );

        // return value based on query type
        return $type === 'select' ? $this : $response;
    }

    //
    // helpers
    //  

    /**
     * function limit
     * @param int $limit
     * @return self
     */

    public function limit(int $limit): self
    {
        // add limit to builderparts
        $this->limit = $limit;

        // return self
        return $this;
    }

    /**
     * function orderBy
     * @param string $column
     * @param string $direction
     * @return self
     */

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        // make direction to uppercase
        $direction = strtoupper($direction);

        // add orderBy to builderparts
        $this->orders[] = compact(
            'column',
            'direction'
        );

        // return self
        return $this;
    }

    /**
     * function groupBy
     * @param string|array $groups
     * @return self
     */

    public function groupBy(...$groups): self
    {
        // loop trough all groups
        foreach ($groups as $group) {
            // add group
            $this->groups = array_merge(
                (array) $this->groups,
                (array) $group
            );
        }

        // return self
        return $this;
    }
}