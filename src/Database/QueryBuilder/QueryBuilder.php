<?php

namespace Framework\Database\QueryBuilder;

use Framework\Database\Connection\Connection;
use Framework\Database\JoinClause\JoinClause;
use Framework\Database\DatabaseHelpers;
use Framework\Database\Grammar\Grammar;
use IteratorAggregate;
use ArrayIterator;
use Exception;
use Closure;

class QueryBuilder extends Grammar implements IteratorAggregate
{
    use DatabaseHelpers;

    /**
     * keeps track of show query's
     * @var bool
     */

    private bool $logSql = false;

    /**
     * fetch mode
     * @var int
     */
    protected int $fetchMode = \PDO::FETCH_OBJ;

    /**
     * builder parts for making database query
     * @var array
     */
    protected array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
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
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * keeps track of offset
     * @var int
     */
    protected ?int $offset = null;

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
     * @var array|null
     */
    private ?array $resetData;

    /**
     * @var Connection|null
     */
    private ?Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        // set reset data
        $this->resetData = get_object_vars($this);

        // set connection class instance
        $this->connection = $connection;
    }

    /**
     * @return $this
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
     * @param string $tableName
     * @param string|array $select
     * @return self
     * @throws Exception
     */
    public function table(string $tableName, string|array $select = ['*']): self
    {
        // add table name
        $this->from = $tableName;

        // make select statement
        // and return self
        return $this->select($select);
    }

    /**
     * Select columns from table
     * @param string|array $select
     * @return self
     * @throws Exception
     */
    public function select(string|array $select = ['*']): self
    {
        // check if column was already set
        if ($key = $this->checkIfColumnWasAlreadySet('*')) {
            unset($this->columns[$key]);
        }

        // make select
        $columns = is_string($select) ? func_get_args() : $select;

        // loop through all columns
        foreach ($columns as $as => $column) {
            // check if is subSelect with as name
            if ($column instanceof Closure) {
                // make subSelect
                $this->columns[] = $this->subSelect($column, $as);
            } // when column is an array
            elseif (is_array($column)) {
                $this->columns = array_merge(
                    $this->columns,
                    $this->selectFormat($column)
                );
            } // else is string
            else {
                // add column to columns array
                $this->columns[] = preg_replace('/^([A-z0-9\_\-]+)$/', '`$1`', trim($column));
            }
        }

        // make select columns unique
        $this->columns = array_unique($this->columns);

        // return self
        return $this;
    }

    /**
     * This method will check if an column already exists
     *
     * @param string $column
     * @return false|integer
     */
    private function checkIfColumnWasAlreadySet(string $column): false|int
    {
        // find column
        $key = array_search($column, $this->columns);

        // check if column was found
        return $key === false ? false : $key;
    }

    /**
     * function subSelect
     * @param string|Closure $query
     * @param string|int $as
     * @return string
     * @throws Exception
     */
    public function subSelect(string|Closure $query, string|int $as): string
    {
        // get bindings from query
        [$query, $bindings] = $this->createSubSelect($query);

        // format subSelect
        if (is_string($as)) {
            return "({$query}) as {$as}";
        } else {
            return "({$query})";
        }
    }

    //
    // WHERE functions
    //

    /**
     * function where
     * @param $column
     * @param array|string|null $operator
     * @param null $value
     * @param string $boolean
     * @return self
     */
    public function where($column, array|string $operator = null, $value = null, string $boolean = 'AND'): self
    {
        // check is instanceof \Closure
        if ($column instanceof Closure && is_null($value)) {
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
        $columns = (array)$column;
        $values = (array)$value;

        // loop through all columns
        foreach ($columns as $key => $column) {
            // get value by column
            $value = $values[$key] ?? null;
            // check if operator is an array
            if (is_array($operator)) {
                $_operator = $operator[$key] ?? '=';
            }

            // force to be real int
            if (is_int($value)) {
                $value = (int)$value;
            }

            // add to where statement
            $this->wheres[] = [
                'type' => 'normal',
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
     * @param string|Closure $query
     * @param mixed $bindData
     * @param string $boolean
     * @return QueryBuilder
     */
    public function whereRaw(string|Closure $query, array $bindData = [], string $boolean = 'AND'): static
    {
        // keep track of query type
        $type = 'raw';

        // check if query is string
        if (is_string($query)) {
            // add to where statement
            $this->wheres[] = compact(
                'type',
                'query',
                'boolean'
            );

            // add bind data to builder parts
            $this->bindings['where'] = array_merge($this->bindings['where'], flattenArray((array) $bindData));

            // return self
            return $this;
        }

        // update query type to
        $type = 'nested';

        // get query
        $query($query = new self($this->connection));

        // add bind data to builder parts
        $this->mergeBindings($this, $query);

        // get formatted where statement with bindData
        $query = $query->formatWhere($this, $query->wheres);

        // add to where statement
        $this->wheres[] = compact(
            'type',
            'query',
            'boolean'
        );

        // return self
        return $this;
    }

    /**
     * function orWhere
     * @param mixed $column Column names from tables
     * @param string|null $operator
     * @param mixed|null $value
     * @return self
     */
    public function orWhere(mixed $column, ?string $operator = null, mixed $value = null): self
    {
        // return self and make where statement with OR
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * function whereIn
     * @param string|Closure $column
     * @param array|null $values
     * @param string $boolean
     * @return self
     * @throws Exception
     */
    public function whereIn(string|Closure $column, ?array $values = null, string $boolean = 'AND'): self
    {
        // check if $column is instance of closure that means that whereIn will be an subWhere
        // the where statement will have ( ) wrapped around it
        if ($column instanceof Closure) {
            // call closure
            $column($query = new self($this->connection));
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
     * function whereExists
     * @param Closure $callback
     * @param string $boolean
     * @param bool $not
     * @return self
     * @throws Exception
     */
    public function whereExists(Closure $callback, string $boolean = 'AND', bool $not = false): self
    {
        // call closure
        $callback($query = new self($this->connection));

        // merge bindings
        $this->mergeBindings($this, $query);

        // get bindings from query
        [$query,] = $this->createSubSelect($query);

        // get type based on not value
        $type = $not ? 'notExists' : 'exists';

        // add to where statement
        $this->wheres[] = compact(
            'type',
            'query',
            'boolean'
        );

        // return self
        return $this;
    }

    /**
     * function whereNotExists
     * @param Closure $callback
     * @param string $boolean
     * @return self
     * @throws Exception
     */
    public function whereNotExists(Closure $callback, string $boolean = 'AND'): self
    {
        return $this->whereExists($callback, $boolean, true);
    }


    /**
     * function whereColumn
     * @param string $column
     * @param string|null $operator
     * @param string|null $value
     * @param string $boolean
     * @return QueryBuilder
     */
    public function whereColumn(string $column, ?string $operator = null, ?string $value = null, string $boolean = 'AND'): static
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

    /**
     * @param string $table
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $value
     * @param string $type
     * @return $this
     */
    public function join(string $table, string|Closure $first, string $operator = null, string $value = null, string $type = 'INNER'): self
    {
        // make instance of
        $join = new JoinClause($this, $table, $type);

        // check if first is instance of closure
        if ($first instanceof Closure) {
            // make closure and make instance of JoinClause
            $first($join);
            // add join query
            $this->joins[] = $join;
        } else {
            $this->joins[] = $join->on($first, $operator, $value);
        }

        // merge bindings
        $this->mergeBindings($this, $join);

        // return self
        return $this;
    }

    /**
     * function leftJoin
     * @param string $table
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $value
     * @return QueryBuilder
     */
    public function leftJoin(string $table, string|Closure $first, string $operator = null, string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'left');
    }

    /**
     * function rightJoin
     * @param string $table
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $value
     * @return QueryBuilder
     */
    public function rightJoin(string $table, string|Closure $first, string $operator = null, string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'right');
    }

    //
    // Fetch methods
    //

    /**
     * function all
     * @param mixed $fallbackReturnType
     * @param int|null $fetchMode
     * @return mixed
     * @throws Exception
     */
    public function all(mixed $fallbackReturnType = false, int $fetchMode = null): mixed
    {
        // return all results
        $returnValue = $this->handleExecution(...$this->selectToSql($this))->fetchAll($fetchMode ?: $this->fetchMode) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    /**
     * function one
     * @param mixed $fallbackReturnType
     * @param int|null $fetchMode
     * @return mixed
     * @throws Exception
     */
    public function one(mixed $fallbackReturnType = false, int $fetchMode = null): mixed
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
     * @param int $column
     * @return mixed
     * @throws Exception
     */
    public function column(mixed $fallbackReturnType = false, int $column = 0): mixed
    {
        // get return value
        $returnValue = $this->handleExecution(...$this->selectToSql($this))->fetchColumn($column) ?: $fallbackReturnType;

        // return fallback return value
        return $this->errorWhileExecuting ? $fallbackReturnType : $returnValue;
    }

    //
    // action methods
    //

    /**
     * function insert
     * @param array $insertData
     * @return bool|int
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function raw(string $query, array $bindData = []): mixed
    {
        $before = $this;
        // handle execution of query
        $response = $this->handleExecution(
            $query,
            flattenArray($bindData),
            $type
        );

        $this->hasBeenExecuted = true;

        // return value based on query type
        return $type === 'select' ? $before : $response;
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
        // add limit to builder parts
        $this->limit = $limit;

        // check if offset was set
        if (!isset($this->offset)) {
            // set offset
            return $this->offset(0);
        }

        // return self
        return $this;
    }

    /**
     * function offset
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        // add offset to builderparts(min value of 0)
        $this->offset = max(0, (int)$offset);

        // return self
        return $this;
    }

    /**
     * function paginate
     * @param int $page
     * @param int $perPage
     * @return array
     * 
     * @throws Exception
     */
    public function paginate(int $page, int $perPage = 15): array
    {
        // clone current query
        $clone = clone $this;

        // overwrite select columns
        $clone->columns = ['count(*) as total_results'];

        // fetch total_results count
        $totalResults = intval($clone->column(0));

        // calculate 
        $totalPages = ceil($totalResults / $perPage);

        // get all results
        $results = $this->offset(($page - 1) * $perPage)->limit($perPage)->all([]);

        // format return data
        return [
            'first_page' => 1,
            'prev_page' => [
                'exists' => $page - 1 > 0,
                'page' => $page - 1 > 0 ? $page - 1 : 1
            ],
            'current_page' => $page > 0 ? $page : 1,
            'next_page' => [
                'exists' => $page + 1 <= $totalPages,
                'page' => $page + 1 > $totalPages ? $totalPages : $page + 1
            ],
            'last_page' => $totalPages,
            'total_pages' => $totalPages,
            'total_results' => $totalResults,
            'per_page' => $perPage,
            'results' => $results,
        ];
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

        // add orderBy to builder parts
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
    public function groupBy(string ...$groups): self
    {
        // loop through all groups
        $this->groups = array_merge($this->groups, flattenArray($groups));

        // return self
        return $this;
    }

    /**
     * @param bool $when
     * @param callable $callback
     * @return QueryBuilder
     */
    public function when(bool $when, callable $callback): self
    {
        // make closure from callback
        $callback = Closure::fromCallable($callback);

        // when
        if ($when) {
            // call callable
            $callback($builder = $this);

            // return new builder
            return $builder;
        }

        // return self
        return $this;
    }

    /**
     * This method will reset all class properties
     * 
     * @return void
     */
    public function reset(): void
    {
        // reset all settings
        foreach ($this->resetData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * This method will return all results when you use queryBuilder without all, one, column inside a foreach
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all([]));
    }
}
