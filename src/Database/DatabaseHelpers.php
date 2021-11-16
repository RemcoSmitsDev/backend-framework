<?php

namespace Framework\Database;

trait DatabaseHelpers
{
    use QueryBuilderV2;

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

    protected function whereFormat($where): array
    {
        // keep track of wheres
        $wheres = [];

        // loop trough all wheres
        foreach ($where as $key => $value) {
            if (is_string($key)) {
                $wheres[$key] = $value;
            } else {
                // merge wheres
                $keys = array_keys($value);
                $values = array_values($where[$key]);

                foreach ($keys as $key => $value) {
                    $wheres[$value] = $values[$key];
                }
            }
        }

        // return wheres
        return $wheres;
    }

    public function whereClosure(\Closure $column, string $boolean)
    {
        // call closure with new instance of database
        $column($query = new static($this->connection));
        // check if there is an where statement
        if (isset($query->bindings['where'][0])) {
            // update boolean when is sub query
            $query->bindings['where'][0]['boolean'] = $boolean;
        }
        // get wheres from sub group
        $this->bindings['where'][] = $query->bindings['where'];

        // return self
        return $this;
    }

    protected function createSubSelect($query): array
    {
        // check if query is instance of \Closure
        if ($query instanceof \Closure) {
            $callback = $query;
            // make instance of DatabaseClass
            $callback($query = new static($this->connection));
        }

        // return formatted
        return $this->parseSub($query);
    }

    protected function parseSub($query): array
    {
        if ($query instanceof DatabaseV2) {
            // return formatted query string with bindings
            return $query->selectToSql($query);
        } elseif (is_string($query)) {
            // return query string with empty bindings
            return [$query,[]];
        } else {
            throw new \Exception("The sub query must be an instanceof DatabaseV2 or an string", 1);
        }
    }

    public function handleFetchQuery()
    {
        // get query and bindData
        [$query, $bindData] = $this->selectToSql($this);

        // make prepared statement query
        $statement = $this->connection->prepare($query);

        // merge bindData
        $this->bindings['bindData'] = array_merge($this->bindings['bindData'], $bindData);

        // check if bindData is empty
        if (!empty($this->bindings['bindData'])) {
            // bind value for prepared statements
            $statement->execute($this->bindings['bindData']);
        } else {
            $statement->execute();
        }

        // close connection
        $this->connection = null;

        // return statement to fetch
        return $statement;
    }
}
