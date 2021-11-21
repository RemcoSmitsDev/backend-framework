<?php

namespace Framework\Database;

use Framework\Database\Connection\Connection;

trait DatabaseHelpers
{
    /**
     * function flattenArray
     * @param array $array
     * @return array
     */
    protected function flattenArray(array $array): array
    {
        // flatten array
        return array_reduce($array, function ($array, $item) {
            // merge flatten array with new value
            return array_merge($array, is_array($item) ? $this->flattenArray($item) : [$item]);
        }, []);
    }

    /**
     * function formatColumnNames
     * @param string $columnName
     * @return string
     */

    public function formatColumnNames(string $columnName): string
    {
        return preg_replace('/([A-z0-9_\-]+)\.([A-z0-9_\-]+)/', '`$1`.`$2`', $columnName);
    }

    /**
     * function selectFormat
     * @param mixed $selectColumn
     * @return array
     */

    protected function selectFormat($selectColumn): array
    {
        // keep track of select columns
        $selectColumns = [];

        // check if select columns is an array
        if (is_array($selectColumn)) {
            // loop trough all select columns
            foreach ($selectColumn as $column) {
                // merge select columns
                $selectColumns = array_merge($selectColumns, $this->selectFormat($column));
            }
        } else {
            $selectColumns[] = $selectColumn;
        }

        // return selectColumns
        return $selectColumns;
    }

    /**
     * function whereFormat
     * @param array $where
     * @return array
     */

    protected function whereFormat(array $where): array
    {
        // keep track of wheres
        $wheres = [];

        // loop trough all wheres
        foreach ($where as $key => $value) {
            // check if key is an string
            if (is_string($key)) {
                $wheres[$key] = $value;
            } else {
                // merge wheres
                $keys = array_keys($value);
                $values = array_values($where[$key]);

                // loop trough all keys
                foreach ($keys as $key => $value) {
                    $wheres[$value] = $values[$key];
                }
            }
        }

        // return wheres
        return $wheres;
    }

    /**
     * function whereClosure
     * @param \Closure $column
     * @param string $boolean
     * @return self
     */

    protected function whereClosure(\Closure $column, string $boolean): self
    {
        // call closure with new instance of database
        $column($query = new static($this->connection));

        // check if there is an where statement
        if (isset($query->wheres[0])) {
            // update boolean when is sub query
            $query->wheres[0]['boolean'] = $boolean;
        }

        // get wheres from sub group
        $this->wheres[] = array_merge(
            (array) $this->wheres,
            (array) $query->wheres
        );

        foreach ($query->bindings as $key => $binding) {
            $this->bindings[$key] = array_merge($this->bindings[$key], $query->bindings[$key]);
        }

        // return self
        return $this;
    }

    /**
     * function createSubSelect
     * @param string|\Closure|Database $query
     * @return array
     */

    protected function createSubSelect(string | \Closure | Database $query): array
    {
        // check if query is instance of \Closure
        if ($query instanceof \Closure) {
            // make instance of DatabaseClass
            $query($query = new static($this->connection));

            // merge bindings
            foreach ($query->bindings as $key => $binding) {
                $this->bindings[$key] = array_merge($this->bindings[$key], $binding);
            }
        }

        // return formatted
        return $this->parseSub($query);
    }

    /**
     * functon parseSub
     * @param mixed $query
     * @return array
     */

    protected function parseSub($query): array
    {
        if ($query instanceof Database) {
            // return formatted query string with bindings
            return $query->selectToSql($query);
        } elseif (is_string($query)) {
            // return query string with empty bindings
            return [$query, []];
        } else {
            throw new \Exception("The sub query must be an instanceof Database or an string", 1);
        }
    }

    /**
     * function handleExecution
     * @param string $query
     * @param array $bindData
     */

    protected function handleExecution(string $query, ?array $bindData, string &$type = null)
    {
        // keep track of return value
        $returnValue = null;

        // get type
        preg_match('/^\s*\b\w+\b/i', $query, $match);

        // check if query log was on
        $this->logSqlQuery($query, $bindData);

        // check if there was an type found
        if (empty($match) || !in_array(strtolower($match[0]), $this->validTypes)) {
            throw new \Exception('You have passed an non valid database query. Valid types: (' . implode(', ', $this->validTypes) . ')', 1);
        }

        // check if connection not already was started
        if ($this->connection instanceof Connection) {
            // try to start connection
            $this->connection = $this->connection->start();
        }

        // get type from match
        $type = strtolower($match[0]);

        // check if has already been executed
        if ($this->hasBeenExecuted) {
            // return insert id
            if ($type === 'insert') {
                // get insert id
                $returnValue = $this->connection->lastInsertId();
            } elseif ($type === 'select') {
                // return statement
                $returnValue = $this->statement;
            }

            // close connection
            $this->connection = null;

            // execution was done(not failures)
            return is_null($returnValue) ? true : $returnValue;
        }

        // prepare database query
        $this->statement = $this->connection->prepare($query);

        // catch when goes wrong
        try {
            // try to execute insert query
            $this->statement->execute(array_values($bindData) ?: null);

            // update has been executed to true
            $this->hasBeenExecuted = true;

            // return insert id
            if ($type === 'insert') {
                // get insert id
                $returnValue = $this->connection->lastInsertId();
            } elseif ($type === 'select') {
                // return statement
                $returnValue = $this->statement;
            }

            // close connection
            $this->connection = null;

            // execution was done(not failures)
            return is_null($returnValue) ? true : $returnValue;
        } catch (\Throwable $error) {
            // set errorWhileExecuting to true
            $this->errorWhileExecuting = true;

            // update has been executed to true
            $this->hasBeenExecuted = true;

            // close connection
            $this->connection = null;

            echo $error->getMessage();

            // return value based on query type
            return $type == 'select' ? $this->statement : false;
        }
    }

    /**
     * function getQueryType
     * @param string $query
     * @return string
     */

    protected function getQueryType(string $query): string
    {
        // get type
        preg_match('/^\s*\b\w+\b/i', $query, $match);

        // check if there was an type found
        if (empty($match) || !in_array(strtolower($match[0]), $this->validTypes)) {
            throw new \Exception("You have passed an non valid database query", 1);
        }

        // get type from match
        return strtolower($match[0]);
    }

    /**
     * function logSqlQuery
     * @param string $query
     * @param array $bindData
     */

    private function logSqlQuery(string $query, array $bindData)
    {
        // check if query log was on
        if (!$this->logSql) {
            return false;
        }

        // map trough formatted
        $formattedBindData = array_map(function ($data) {
            // check if data is bool
            if (is_bool($data)) {
                return $data ? 'true' : 'false';
            }
            // return data
            return $data;
        }, $bindData);

        // echo query
        echo $query . ' --- bindings: (' . implode(',', $formattedBindData) . ')<br>';
    }
}