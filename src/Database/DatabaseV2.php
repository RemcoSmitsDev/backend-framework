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

    protected array $bindings = [
        'select' => [],
        'from' => '',
        'join' => [],
        'where' => [],
        'bindData' => [],
        'groupBy' => [],
        // 'having' => [],
        'order' => [],
        // 'union' => [],
        // 'unionOrder' => [],
    ];

    public function __construct(Connection|\PDO $connection = null)
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

    public function table(string $tableName, string|array $select = '*'): self
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

    public function select(string|array $select = ['*']): self
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

    public function subSelect(string|\Closure $query, $as)
    {
        // get bindings from query
        [$query,$bindings] = $this->createSubSelect($query, $as);

        // update bindData
        $this->bindings['bindData'] = array_merge($this->bindings['bindData'], $bindings);

        // format subSelect
        $this->bindings['select'][] = "({$query}) as {$as}";
    }


    //
    // WHERE functions
    //

    public function where($column, $operator = null, $value = null, $boolean = 'AND'): self
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
            $_value = $values[$key] ?? null;
            // check if operator is an array
            if (is_array($operator)) {
                $_operator = $operator[$key] ?? '=';
            }

            // force to be real int
            if (is_int($value)) {
                $value = (int)$value;
            }

            // add to where statement
            $this->bindings['where'][] = [
              'column' => $column,
              'operator' => $_operator ?? $operator,
              'value' => $value,
              'boolean' => $boolean
            ];
        }

        // return self
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string|\Closure $column, array $values, string $boolean = 'AND'): self
    {
        if ($column instanceof \Closure) {
            // call closure
            $column($query = new static($this->connection));
            // get bindings from query
            [$query,$bindings] = $this->createSubSelect($query);

            // add to where statement
            $this->bindings['where'][] = [
              'type' => 'raw',
              'column' => 'id',
              'operator' => 'IN',
              'value' => '('.$query.')',
              'boolean' => $boolean
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
          'boolean' => $boolean
        ];
        // merge bindData
        $this->bindings['bindData'][] = implode(',', $values);

        return $this;
    }

    //
    // Fetch methods
    //

    public function all($fallbackReturnType = false)
    {
        // return all results
        return $this->handleFetchQuery()->fetchAll($this->fetchMode) ?: $fallbackReturnType;
    }

    public function one($fallbackReturnType = false)
    {
        // return all results
        return $this->handleFetchQuery()->fetch($this->fetchMode) ?: $fallbackReturnType;
    }

    public function get($fallbackReturnType = false)
    {
        // return all results
        return $this->one($fallbackReturnType);
    }
}
