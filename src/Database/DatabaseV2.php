<?php

namespace Framework\Database;

use Framework\Database\Connection\Connection;
use Framework\Database\DatabaseHelpers;

class DatabaseV2
{
    use DatabaseHelpers;

    // keep track of connection
    private $connection;

    // fetch mode
    protected $fetchMode = \PDO::FETCH_OBJ;

    // bindings for making database query
    protected array $bindings = [
        'select' => [],
        'from' => '',
        'join' => [],
        'where' => [],
        'bindData' => [],
        'limit' => '',
        'groupBy' => [],
        'orderBy' => [],
    ];

    // keep track if there went something wrong
    protected bool $errorWhileExecuting = false;

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
     * select table with option for select columns
     * @param string       $tableName
     * @param string|array $select
     * @return self
     */

    public function table(string $tableName, string | array $select = '*'): self
    {
        // add tablename
        $this->bindings['from'] = $tableName;

        // make select statement
        $this->select($select);

        // return self
        return $this;
    }

    /**
     * Select columns from table
     * @param string|array $select
     * @return self
     */

    public function select(string | array $select = ['*']): self
    {
        // make select
        $columns = is_array($select) ? $select : (array) $select;

        // loop trough all columns
        foreach ($columns as $as => $column) {
            // check if is subSelect with as name
            if (is_string($as) && $column instanceof \Closure) {
                // make subSelect
                $this->subSelect($column, $as);
            }
            // when column is an array
            elseif (is_array($column)) {
                $this->bindings['select'] = array_merge($this->bindings['select'], $this->selectFormat($column));
            }
            // else is string
            else {
                // trim spaces
                $column = trim($column);
                // check if * is in column string
                if (preg_match('/\*|count\(.+?\)|DISTINCT/is', $column)) {
                    $this->bindings['select'][] = "$column";
                } else {
                    $this->bindings['select'][] = "`$column`";
                }
            }
        }

        // make select columns unique
        $this->bindings['select'] = array_unique($this->bindings['select']);

        // return self
        return $this;
    }

    /**
     * functin subSelect
     * @param string|\Closure $query
     * @param string          $as
     */

    public function subSelect(string | \Closure $query, string $as): void
    {
        // get bindings from query
        [$query, $bindings] = $this->createSubSelect($query, $as);

        // update bindData
        $this->bindings['bindData'] = array_merge($this->bindings['bindData'], $bindings);

        // format subSelect
        $this->bindings['select'][] = "({$query}) as {$as}";
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
            $this->bindings['where'][] = [
                'column' => $column,
                'operator' => $_operator ?? $operator,
                'value' => $value,
                'boolean' => $boolean,
            ];
        }

        // return self
        return $this;
    }

    /**
     * function orWhere
     * @param $column   Column names from tables
     * @param $operator
     * @param $value
     */

    public function orWhere($column, $operator = null, $value = null)
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

    public function whereIn(string | \Closure $column, array $values, string $boolean = 'AND'): self
    {
        // check if $column is instance of closure that means that whereIn will be an subWhere
        // the where statement will have ( ) wrapped around it
        if ($column instanceof \Closure) {
            // call closure
            $column($query = new static($this->connection));
            // get bindings from query
            [$query, $bindings] = $this->createSubSelect($query);

            // add to where statement
            $this->bindings['where'][] = [
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
        $this->bindings['where'][] = [
            'type' => 'raw',
            'column' => $column,
            'operator' => 'IN',
            'value' => '(?)',
            'boolean' => $boolean,
        ];
        // merge bindData
        $this->bindings['bindData'][] = implode(',', $values);

        return $this;
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
        $returnValue = $this->handleFetchQuery()->fetchAll($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

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
        // return one result
        $returnValue = $this->handleFetchQuery()->fetch($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

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
        $returnValue = $this->handleFetchQuery()->fetchColumn($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    //
    // action methods
    //

    /**
     * function insert
     * @param array $insert
     */

    public function insert(array $insertData)
    {
        // check if there exists an table
        if (empty($this->bindings['from'])) {
            return false;
        }

        // return insert id(s)
        return $this->handleInsertExecution(...$this->insertToSql($this, $insertData));
    }
}
